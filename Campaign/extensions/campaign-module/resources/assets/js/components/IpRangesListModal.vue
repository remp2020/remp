<style>
    .ip-ranges-textarea {
        font-family: monospace;
        white-space: pre;
    }

    .ip-ranges-ignored code {
        word-break: break-all;
    }
</style>

<template>
    <div class="modal" id="modal-ip-ranges" style="z-index: 15;" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header p-15">
                    <h4 class="modal-title">IP addresses / ranges</h4>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        One IP address or range per line. A range is two addresses on one line, e.g.
                        <code>192.168.1.0 - 192.168.1.255</code> or <code>192.168.1.0,192.168.1.255</code>.
                        You can paste an export (CSV, spreadsheet): on each line the first IP address (IPv4 or IPv6)
                        starts the range, the second (optional) ends it; other columns and lines without an IP address are ignored.
                    </p>
                    <div class="form-group fg-line m-b-10">
                        <textarea v-model="text" class="form-control ip-ranges-textarea" rows="15" :placeholder="'192.168.1.1\n10.0.0.0 - 10.0.0.255\n2001:db8::1'"></textarea>
                    </div>
                    <div>
                        Ranges: <strong>{{ parsed.ranges.length }}</strong>
                        <span v-if="parsed.duplicates"> &middot; duplicate lines merged: {{ parsed.duplicates }}</span>
                        <span v-if="parsed.ignored.length" class="c-red"> &middot; lines without IP address ignored: {{ parsed.ignored.length }}</span>
                    </div>
                    <ul v-if="parsed.ignored.length" class="text-muted m-t-10 pre-scrollable ip-ranges-ignored">
                        <li v-for="line in ignoredShown"><code>{{ line }}</code></li>
                        <li v-if="ignoredHidden">&hellip; and {{ ignoredHidden }} more</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info waves-effect" @click="apply" data-dismiss="modal"><i class="zmdi zmdi-check"></i> Apply</button>
                    <button type="button" class="btn btn-default waves-effect" data-dismiss="modal"><i class="zmdi zmdi-close"></i> Cancel</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import {formatIpRange, parseIpRangeList} from "./_ipRanges";

    const IGNORED_LINES_SHOWN = 100;

    export default {
        data() {
            return {
                text: "",
            };
        },
        computed: {
            parsed: function () {
                return parseIpRangeList(this.text);
            },
            ignoredShown: function () {
                return this.parsed.ignored.slice(0, IGNORED_LINES_SHOWN);
            },
            ignoredHidden: function () {
                return this.parsed.ignored.length - this.ignoredShown.length;
            },
        },
        methods: {
            open: function (ranges) {
                this.text = ranges.map(formatIpRange).join("\n");
                $("#modal-ip-ranges").modal('show');
            },
            apply: function () {
                this.$emit('apply', this.parsed.ranges.slice());
            },
        },
    }
</script>
