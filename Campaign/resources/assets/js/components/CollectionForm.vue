<template>
    <div>
        <div class="row">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" v-model="name" id="name" name="name" placeholder="Name" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-8 col-sm-12">
                            <label for="countries_blacklist" class="fg-label">Add Campaign</label>
                        </div>
                        <div class="col-md-8 col-sm-12 m-b-10">
                            <v-select v-model="selectedCampaigns"
                                      id="campaigns"
                                      :name="'campaigns[]'"
                                      :options.sync="availableCampaigns"
                                      :multiple="true"
                                      ref="campaignsSelect"
                            ></v-select>
                        </div>
                    </div><!-- .row -->

                </div>
            </div>
        </div>
        <div class="row m-t-10 m-l-30">
            <div class="col-md-12">
                <div class="row m-b-10" v-for="(id,i) in selectedCampaigns" style="line-height: 25px">
                    <div class="col-md-12 text-left">
                        {{ getCampaignName(id) }}
                        <div class="pull-left m-r-20">
                            <span v-on:click="removeCampaign(i, id)" class="btn btn-sm bg palette-Red waves-effect p-5 remove-campaign">&times;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-lg-8">
                <div class="input-group m-t-20 m-b-30">
                    <div class="fg-line">
                        <input type="hidden" name="action" :value="submitAction">

                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save'">
                            <i class="zmdi zmdi-check"></i> Save
                        </button>
                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save_close'">
                            <i class="zmdi zmdi-mail-send"></i> Save and close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";

    let props = [
        "_name",
        "_action",
        "_selectedCampaigns",
        "_allCampaigns",
    ];

    export default {
        components: {
            vSelect,
        },
        created: function(){
            let self = this;

            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        props: props,
        data: function() {
            return {
                "name": null,
                "selectedCampaigns": [],
                "allCampaigns": [],
                "submitAction": null,
            }
        },
        computed: {
            availableCampaigns: function() {
                return [
                    ...this.allCampaigns.map((campaign) => {
                        console.log('campaign', campaign);
                        return {
                            "label": campaign.name,
                            "value": campaign.id,
                        }
                    }),
                ];
            },
        },
        methods: {
            removeCampaign: function(index, id) {
                this.selectedCampaigns.splice(index, 1);
                this.$refs.campaignsSelect.unselectValue(id);
            },
            getCampaignName: function(id) {
                let campaign = this.availableCampaigns.find((campaign) => {
                    return campaign.value === parseInt(id);
                });

                return campaign.label;
            },
        }
    }
</script>
