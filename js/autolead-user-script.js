jQuery(document).ready(function() {

    jQuery('.autolead-widget:first-child').css('display', 'block');

    jQuery('.autolead-answer ul li').on('click', function(e) {
        e.preventDefault();
        var $this = jQuery(this);
        $this.siblings().removeClass('active').addClass('inactive');
        $this.removeClass('inactive').addClass('active');
    });

    jQuery('.choice-selector > div').on('click', function() {
        var $this = jQuery(this);
        $this.siblings().removeClass('choice-active').addClass('choice-inactive');
        $this.removeClass('choice-inactive').addClass('choice-active');
    });


    /**
     * 
     * @type type
     */

    jQuery('.autolead-action').on('click', function() {
        var $this = jQuery(this);
        var current_widget = $this.closest('.autolead-widget');
        var widget_wrapper = current_widget.closest('.autolead-widget-wrapper');
        var autolead_id = widget_wrapper.find('[name=autolead_post_id]').val();
        var question = current_widget.find('.autolead-title').text();
        var answer_wrapper = current_widget.find('.autolead-answer');
        var answer_type = answer_wrapper.data('type');
        var answer;

        /**
         * Get the right value inside the html
         */
        switch (answer_type) {
            case 'input':
                answer = answer_wrapper.find('.autolead-input').val();
                break;

            case 'textarea':
                answer = answer_wrapper.find('.autolead-textarea').val();
                break;

            case 'list':
                answer = answer_wrapper.find('.autolead-list li.active span[data-editable="yes"]').text();
                break;

            case 'choice':
                answer = answer_wrapper.find('.choice-selector .choice-active').text();
                break;

            default:
                answer = 'na';
                break;
        }

        var survey_data = {};
        if (widget_wrapper.data('survey') && widget_wrapper.data('survey') !== 'undefined') {
            survey_data = widget_wrapper.data('survey');
        }
        if (!widget_wrapper.data('survey_completed')) {
            survey_data[question] = answer;
        }
        widget_wrapper.data('survey', survey_data);

        if (current_widget.is(':not(.autolead-widget:last-of-type)')) {
            var next_widget = current_widget.next('.autolead-widget');
            next_widget.css('display', 'block');
            current_widget.css('display', 'none');
        } else {

            if ('no' === $this.data('form_processed')) {
                var name = $this.closest('.autolead-content').find('.autolead-answer input[name*=name]');
                var email = $this.closest('.autolead-content').find('.autolead-answer input[name*=email]');
                jQuery.ajax({
                    url: autolead_vars.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        nonce: autolead_vars.nonce,
                        action: 'autolead_process_form',
                        autolead_id: autolead_id,
                        process_type: $this.data('process_type'),
                        survey_data: widget_wrapper.data('survey'),
                        name: $this.closest('.autolead-content').find('.autolead-answer input[name*=name]').val(),
                        email: $this.closest('.autolead-content').find('.autolead-answer input[name*=email]').val(),
                        getname: $this.closest('.autolead-content').find('.autolead-answer input[name=autolead_getname]').val(),
                        check_all: email.length && name.length ? true : false,
                        valid_field: name.length == 0 ? 'email' : 'name'
                    },
                    beforeSend: function() {
                        $this.closest('.autolead-content').append('<div class="al-waiting">Processing Please Wait...</div>');
                    },
                    success: function(response) {

                        $this.closest('.autolead-content').find('.al-waiting').fadeOut(500, function() {
                            jQuery(this).remove();
                        });

                        if (response.error) {
                            $this.closest('.autolead-content').append('<div class="al-error">' + response.error + '</div>');
                            setTimeout(function() {
                                $this.closest('.autolead-content').find('div.al-error').fadeOut(500, function() {
                                    jQuery(this).remove();
                                });
                            }, 2000);
                            return;
                        }

                        if (response.submit_arform) {
                            $this.closest('.autolead-content').append('<div class="al-waiting">' + response.success + '</div>');
                            setTimeout(function() {
                                $this.closest('.autolead-content').find('.al-waiting').fadeOut(500, function() {
                                    jQuery(this).remove();
                                    $this.closest('.autolead-content').find('form').submit();
                                });
                            }, 2000);
                        } else {
                            location.reload();
                        }
                    }
                });

            } else {
                /**
                 * This is executed only once
                 */
                jQuery.ajax({
                    url: autolead_vars.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        nonce: autolead_vars.nonce,
                        action: 'autolead_pull_arcode',
                        autolead_id: autolead_id
                    },
                    beforeSend: function() {
                        $this.closest('.autolead-content').append('<div class="al-waiting">Processing Please Wait...</div>');
                    },
                    success: function(response) {
                        widget_wrapper.data('survey_completed', true);
                        $this.closest('.autolead-content').find('.al-waiting').fadeOut(500, function() {
                            jQuery(this).remove();
                        });
                        if (response.form_code) {
                            current_widget.find('.autolead-title').html('Submit the Survey');
                            $this.html('Submit');
                            $this.data('form_processed', 'no');
                            $this.data('process_type', 'arform');
                            $this.closest('.autolead-content').find('.autolead-answer').html(response.form_code);
                            $this.closest('.autolead-content').find('.autolead-answer').find('input[type=text],input[type=email]').addClass('.autolead-input')
                        } else if (response.get_name) {
                            current_widget.find('.autolead-title').html('Submit the Survey');
                            $this.html('Submit');
                            $this.data('form_processed', 'no');
                            $this.data('process_type', 'getname');
                            $this.closest('.autolead-content').find('.autolead-answer').html(response.get_name);
                        } else {
                            $this.closest('.autolead-content').append('<div class="al-error">' + response.error + '</div>');
                            setTimeout(function() {
                                $this.closest('.autolead-content').find('div.al-error').fadeOut(500, function() {
                                    jQuery(this).remove();
                                });
                            }, 2000);
                        }
                    }
                });
            }
        }
    });


});