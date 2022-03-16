<script type="text/javascript">
import Axios from "axios";
import EventHub from "./EventHub";

export default {
  name: "iota-service",
  props: {
    articleIds: {
      type: Array,
      required: true
    },
    articleDetailId: {
      type: String,
      required: false
    },
    pageviewMinuteRanges: {
      type: Array,
      default: function() {
        return [
          // { minutes: 15, label: "15m", order: 0 },
          { minutes: undefined, label: "Total", order: 1 }
        ];
      }
    },
    variantsMinuteRanges: {
      type: Array,
      default: function() {
        return [
          { minutes: 15, label: "15m", order: 0 },
          { minutes: undefined, label: "Total", order: 1 }
        ];
      }
    },
    baseUrl: {
      type: String,
      required: true
    },
    configUrl: {
      type: String,
      required: false
    },
    httpHeaders: {
      type: Object,
      default: function() {
        return {};
      }
    },
    onArticleDetail: {
      type: Boolean,
      required: true
    }
  },
  data: function() {
    return {
      config: null
    };
  },
  created: function() {
    EventHub.$on("metrics-settings-changed", this.refetchAllData);

    let vm = this;
    Object.keys(this.httpHeaders).forEach(function(name) {
      Axios.defaults.headers.common[name] = vm.httpHeaders[name];
    });
    Axios.defaults.headers.common["Content-Type"] = "application/json";

    new Promise((resolve, reject) => {
      if (this.configUrl) {
        Axios.get(this.configUrl).then(({ data }) => resolve(data), reject);
      } else {
        resolve();
      }
    }).then(config => {
      if (config) {
        EventHub.$emit("config-changed", config);
      }

      this.refetchAllData();
    });
  },
  methods: {
    refetchAllData(deviceType, articleLocked, subscriber, timeframe) {
      const now = new Date();

      this.fetchAllConcurrents();
      if (this.onArticleDetail) {
        this.fetchConcurrents(this.articleDetailId);
      } else {
        this.fetchConcurrents(null, window.location.href);
      }
      this.fetchReadProgressStats(deviceType, articleLocked, timeframe);
      this.fetchCommerceStats(deviceType);
      this.fetchPageviewStats(now, deviceType, subscriber);
      this.fetchVariantStats(
        now,
        ["title_variant", "image_variant"],
        deviceType,
        subscriber
      );
    },
    fetchCommerceStats: function(deviceType = "all") {
      if (!this.articleIds.length) {
        return;
      }

      const payload = {
        filter_by: [
          {
            tag: "article_id",
            values: this.articleIds
          }
        ],
        group_by: ["article_id"]
      };

      if (deviceType !== "all") {
        payload.filter_by.push({
          tag: "derived_ua_device",
          values: [deviceType]
        });
      }

      Axios.post(
        `${this.baseUrl}/api/journal/commerce/steps/purchase/count`,
        payload
      )
        .then(function(response) {
          let counts = {};
          for (const group of response.data) {
            counts[group["tags"]["article_id"]] = group["count"];
          }
          EventHub.$emit("content-conversions-counts-changed", counts);
        })
        .catch(function(error) {
          console.warn(error);
        });
    },

    fetchPageviewStats: function(now, deviceType = "all", subscriber = "true") {
      if (!this.articleIds.length) {
        return;
      }

      for (let range of this.pageviewMinuteRanges) {
        const payload = {
          filter_by: [
            {
              tag: "article_id",
              values: this.articleIds
            }
          ],
          group_by: ["article_id"]
        };

        if (subscriber !== "all") {
          payload.filter_by.push({
            tag: "subscriber",
            values: ["true"],
            inverse: subscriber === "true" ? false : true
          });
        }

        if (deviceType !== "all") {
          payload.filter_by.push({
            tag: "derived_ua_device",
            values: [deviceType]
          });
        }

        if (range.minutes !== undefined) {
          let d = new Date(now.getTime());
          d.setMinutes(d.getMinutes() - range.minutes);
          payload["time_after"] = d.toISOString();
        }

        Axios.post(
          `${this.baseUrl}/api/journal/pageviews/actions/load/unique/browsers`,
          payload
        )
          .then(function(response) {
            let counts = {};
            for (const group of response.data) {
              counts[group["tags"]["article_id"]] = group["count"];
            }
            EventHub.$emit("content-pageviews-changed", range, counts);
          })
          .catch(function(error) {
            console.warn(error);
          });
      }

      Axios.post(`${this.baseUrl}/api/journal/concurrents/count/articles`, {
        external_id: this.articleIds
      })
        .then(response => {
          EventHub.$emit("content-concurrents-changed", response.data.articles);
        })
        .catch(error => {
          console.warn(error);
        });
    },

    fetchVariantStats: function(
      now,
      variantTypes,
      deviceType = "all",
      subscriber = "true"
    ) {
      if (!this.articleIds.length) {
        return;
      }
      for (let range of this.variantsMinuteRanges) {
        const variantPayload = {
          filter_by: [
            {
              tag: "article_id",
              values: this.articleIds
            },
            {
              tag: "derived_referer_medium",
              values: ["internal"]
            }
          ],
          group_by: ["article_id"].concat(variantTypes)
        };

        if (subscriber !== "all") {
          variantPayload.filter_by.push({
            tag: "subscriber",
            values: ["true"],
            inverse: subscriber === "true" ? false : true
          });
        }

        if (deviceType !== "all") {
          variantPayload.filter_by.push({
            tag: "derived_ua_device",
            values: [deviceType]
          });
        }

        if (range.minutes !== undefined) {
          let d = new Date(now.getTime());
          d.setMinutes(d.getMinutes() - range.minutes);
          variantPayload["time_after"] = d.toISOString();
        }

        Axios.post(
          `${this.baseUrl}/api/journal/pageviews/actions/load/unique/browsers`,
          variantPayload
        )
          .then(function(response) {
            let counts = {};
            for (const group of response.data) {
              if (parseInt(group["count"]) === 0) {
                continue;
              }

              for (const variantType of variantTypes) {
                const variant = group["tags"][variantType];
                if (variant === "") {
                  continue;
                }
                const articleId = group["tags"]["article_id"];
                if (!counts[variantType]) {
                  counts[variantType] = {};
                }
                if (!counts[variantType][articleId]) {
                  counts[variantType][articleId] = {};
                }
                if (!counts[variantType][articleId][variant]) {
                  counts[variantType][articleId][variant] = 0;
                }
                counts[variantType][articleId][variant] += group["count"];
              }
            }

            EventHub.$emit(
              "content-variants-changed",
              variantTypes,
              range,
              counts
            );
          })
          .catch(function(error) {
            console.warn(error);
          });
      }
    },

    fetchReadProgressStats(
      deviceType = "all",
      articleLocked = "false",
      timeframe = 10 * 60 * 1000
    ) {
      const payload = {
        filter_by: [],
        count_histogram: {
          field: "page_progress",
          interval: 0.01
        }
      };

      if (this.onArticleDetail) {
        if (!this.articleDetailId) {
          console.warn("remplib: Unable to fetch reading progress stats. Config states we're on article, but iota.articleSelector doesn't match any");
          return;
        }
        payload.filter_by.push(
          {
            tag: "article_id",
            values: [this.articleDetailId]
          },
          {
            tag: "locked",
            values: [articleLocked]
          }
        );
      } else {
        payload.filter_by.push({
          tag: "url",
          values: [window.location.href]
        });
        payload.time_after = new Date(new Date() - timeframe).toISOString();
      }

      if (deviceType !== "all") {
        payload.filter_by.push({
          tag: "derived_ua_device",
          values: [deviceType]
        });
      }

      Axios.post(
        `${this.baseUrl}/api/journal/pageviews/actions/progress/count`,
        payload
      )
        .then(function(response) {
          if (!response.data.length) {
            return;
          }
          EventHub.$emit(
            "read-progress-data-changed",
            response.data[0].count,
            response.data[0].count_histogram || []
          );
        })
        .catch(function(error) {
          console.warn(error);
        });
    },
    fetchAllConcurrents() {
      Axios.get(`${this.baseUrl}/api/journal/concurrents/count`)
        .then(response => {
          EventHub.$emit("all-concurrents-changed", response.data.total);
        })
        .catch(error => {
          console.warn(error);
        });
    },
    fetchConcurrents(articleId = null, url = null) {
      let urlParam;
      if (articleId) {
        urlParam = `?external_id[]=${articleId}`;
      } else {
        urlParam = `?url[]=${url}`;
      }
      Axios.get(
        `${this.baseUrl}/api/journal/concurrents/count/articles${urlParam}`
      )
        .then(({ data }) => {
          if (data.articles && data.articles.length) {
            EventHub.$emit("page-concurrents-changed", data.articles[0].count);
          }
        })
        .catch(error => {
          console.warn(error);
        });
    }
  }
};
</script>
