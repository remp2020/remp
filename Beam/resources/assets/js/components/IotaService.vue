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
          { minutes: 15, label: "15m", order: 0 },
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
    refetchAllData(deviceType, articleLocked, subscriber) {
      const now = new Date();

      if (this.onArticleDetail) {
        this.fetchReadProgressStats(deviceType, articleLocked);
      }

      this.fetchCommerceStats(deviceType, subscriber);
      this.fetchPageviewStats(now, deviceType, subscriber);
      this.fetchVariantStats(
        now,
        ["title_variant", "image_variant"],
        deviceType,
        subscriber
      );
    },
    fetchCommerceStats: function(deviceType = "all", subscriber = "all") {
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

      Axios.post(
        this.baseUrl + "/journal/commerce/steps/purchase/count",
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

    fetchPageviewStats: function(now, deviceType = "all", subscriber = "all") {
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
          this.baseUrl + "/journal/pageviews/actions/load/unique/browsers",
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
    },

    fetchVariantStats: function(
      now,
      variantTypes,
      deviceType = "all",
      subscriber = "all"
    ) {
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
          this.baseUrl + "/journal/pageviews/actions/load/unique/browsers",
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

    fetchReadProgressStats(deviceType = "all", articleLocked = "false") {
      const payload = {
        filter_by: [
          {
            tag: "article_id",
            values: [this.articleDetailId]
          },
          {
            tag: "locked",
            values: [articleLocked]
          }
        ],
        group_by: ["article_id"],
        count_histogram: {
          field: "page_progress",
          interval: 0.01
        }
      };

      if (deviceType !== "all") {
        payload.filter_by.push({
          tag: "derived_ua_device",
          values: [deviceType]
        });
      }

      Axios.post(
        this.baseUrl + "/journal/pageviews/actions/progress/count",
        payload
      )
        .then(function(response) {
          EventHub.$emit(
            "read-progress-data-changed",
            response.data[0].count,
            response.data[0].count_histogram
          );
        })
        .catch(function(error) {
          console.warn(error);
        });
    }
  }
};
</script>
