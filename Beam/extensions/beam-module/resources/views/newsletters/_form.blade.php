<div id="newsletters-form">

    <form-validator url="{{route('newsletters.validateForm')}}"></form-validator>

    <div class="row">
        <div class="col-md-6 form-group">
            <div class="input-group fg-float m-t-10">
                <span class="input-group-addon"><i class="zmdi zmdi-label"></i></span>
                <div class="fg-line">
                    {{ html()->label('Name')->attribute('class', 'fg-label') }}
                    {{ html()->text('name', $newsletter->name)->attribute('class', 'form-control fg-input') }}
                </div>
            </div>

            <div class="input-group">
                <?php $disabled = empty($segments); ?>
                <span class="input-group-addon"><i class="zmdi <?= $disabled ? 'zmdi-close-circle palette-Red text' : 'zmdi-wallpaper' ?>"></i></span>
                <div class="row">
                    <div class="col-md-12 form-group">
                        {{ html()->label('Segment')->attribute('class', 'fg-label') }}
                        {{ html()->select('segment', $segments, $newsletter->segment)->attributes(
                            array_filter([
                                'class' => 'selectpicker',
                                'data-live-search' => 'true',
                                'placeholder' => !$disabled ? 'Please select...' : 'No segments are available on Mailer, please configure them first',
                                'disabled' => $disabled ? 'disabled' : null
                            ])
                       ) }}
                    </div>
                </div>
            </div>

            <div class="input-group m-t-10">
                <?php $disabled = $generators->isEmpty(); ?>
                <span class="input-group-addon"><i class="zmdi <?= $disabled ? 'zmdi-close-circle palette-Red text' : 'zmdi-settings' ?>"></i></span>
                <div class="row">
                    <div class="col-md-12 form-group">
                        {{ html()->label('Generator')->attribute('class', 'fg-label') }}
                        {{ html()->select('mailer_generator_id', $generators, $newsletter->mailer_generator_id)->attributes(
                            array_filter([
                                'class' => 'selectpicker',
                                'data-live-search' => 'true',
                                'placeholder' => !$disabled ? 'Please select...' : 'No source templates using best_performing_articles generator were configured on Mailer',
                                'disabled' => $disabled ? 'disabled' : null,
                           ])
                       ) }}
                    </div>
                </div>
            </div>

            <div class="input-group m-t-10">
                <?php $disabled = $mailTypes->isEmpty(); ?>
                <span class="input-group-addon"><i class="zmdi <?= $disabled ? 'zmdi-close-circle palette-Red text' : 'zmdi-settings' ?>"></i></span>
                <div class="row">
                    <div class="col-md-12 form-group">
                        {{ html()->label('Mail Type')->attribute('class', 'fg-label') }}
                        {{ html()->select('mail_type_code', $mailTypes, $newsletter->mail_type_code)->attributes(
                            array_filter([
                                'class' => 'selectpicker',
                                'data-live-search' => 'true',
                                'placeholder' => !$disabled ? 'Please select...' : 'No mail types are available on Mailer, please configure them first',
                                'disabled' => $disabled ? 'disabled' : null,
                            ])
                       ) }}
                    </div>
                </div>
            </div>

            <h5>Articles selection</h5>

            <div class="input-group m-t-20">
                <span class="input-group-addon"><i class="zmdi zmdi-key"></i></span>
                <div class="row">
                    <div class="col-md-12 form-group">
                        {{ html()->label('Criterion')->attribute('class', 'fg-label') }}
                        {{ html()->select('criteria', $criteria, $newsletter->criteria)->attributes([
                           'class' => 'selectpicker',
                           'placeholder' => 'Please select...',
                        ]) }}
                    </div>
                </div>
            </div>

            <div class="input-group m-t-10">
                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                <div class="fg-line">
                    <label class="fg-label">Criterion timespan (how old articles are included)</label>
                    {{ html()->text('timespan', $newsletter->timespan)->attributes([
                        'class' => 'form-control fg-input',
                        'placeholder' => "e.g. 3d 1h 4m",
                        'required' => 'required',
                    ]) }}
                </div>
            </div>

            <div class="input-group m-t-15">
                <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
                <div class="fg-line">
                    {{ html()->label('How many articles')->attribute('class', 'fg-label') }}
                    {{ html()->number('articles_count', $newsletter->articles_count, 1, 100)->attribute('class', 'form-control fg-input') }}
                </div>
            </div>

            <div class="input-group m-t-30 checkbox large-tooltip">
                <label class="m-l-15">
                    Personalized content
                    {{ html()->checkbox('personalized_content', $newsletter->personalized_content) }} <i class="input-helper"></i>
                    <span data-toggle="tooltip"
                          data-original-title="For each user, select only those articles he/she has not read yet."
                          class="glyphicon glyphicon-question-sign"></span>
                </label>
            </div>

            <h5 class="m-t-30">Email parameters</h5>

            <div class="input-group m-t-20">
                <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
                <div class="fg-line">
                    {{ html()->label('Email subject')->attribute('class', 'fg-label') }}
                    {{ html()->text('email_subject', $newsletter->email_subject)->attributes([
                        'class' => 'form-control fg-input',
                        'placeholder' => 'e.g. "Top 10 articles this week"',
                    ]) }}
                </div>
            </div>

            <div class="input-group m-t-10">
                <span class="input-group-addon"><i class="zmdi zmdi-arrow-right"></i></span>
                <div class="fg-line">
                    {{ html()->label('Email from')->attribute('class', 'fg-label') }}
                    {{ html()->text('email_from', $newsletter->email_from)->attributes([
                        'class' => 'form-control fg-input',
                        'placeholder' => 'e.g. REMP <info@remp2020.com>',
                    ]) }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <h5>Start date and recurrence</h5>
            <div class="m-t-20">
                {{ html()->hidden('starts_at', $newsletter->starts_at) }}
                {{ html()->hidden('recurrence_rule', $newsletter->recurrence_rule_inline) }}
                @php
                    $recurrence = old('recurrence_rule', $newsletter->recurrence_rule_inline);
                    $recurrence = $recurrence !== null ? "'{$recurrence}'" : 'null';
                @endphp
                <recurrence-selector
                        start-date="{{ old('starts_at', $newsletter->starts_at) }}"
                        :recurrence="{{ $recurrence }}"
                        :callback="callback">
                </recurrence-selector>
            </div>
        </div>

        <div class="col-md-6">
            <h5 v-show="rrule" style="margin-top: 14px">Next few recurrences (up to 10):</h5>
            <rule-ocurrences :rrule='rrule'></rule-ocurrences>
        </div>

        <div class="col-md-12">
            <div class="input-group m-t-10">
                <div class="fg-line">
                    <input type="hidden" name="action" :value="submitAction">

                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save" @click="submitAction = 'save'">
                        <i class="zmdi zmdi-check"></i> Save
                    </button>
                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save_close" @click="submitAction = 'save_close'">
                        <i class="zmdi zmdi-mail-send"></i> Save and close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">

    function formatDateUtc(d) {
        return d.getUTCFullYear() + "-" + ("0"+(d.getUTCMonth()+1)).slice(-2) + "-" + ("0"+d.getUTCDate()).slice(-2) +
             " " + ("0" + d.getUTCHours()).slice(-2) + ":" + ("0" + d.getUTCMinutes()).slice(-2) + ":" + ("0" + d.getUTCSeconds()).slice(-2);
    }

    new Vue({
        el: "#newsletters-form",
        components: {
            RecurrenceSelector, RuleOcurrences, FormValidator
        },
        data: function() {
            return {
                rrule: null,
                submitAction: null
            }
        },
        methods: {
            callback: function (startsAt, recurrenceRule) {
                this.rrule = recurrenceRule
                $('[name="starts_at"]').val(formatDateUtc(new Date(startsAt)));
                $('[name="recurrence_rule"]').val(recurrenceRule);
            }
        }
    });
</script>
