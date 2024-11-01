jQuery.noConflict();

jQuery(document).ready(function() {

    /**
     * Makes the widget top icon draggable
     */
    jQuery('.autolead-toolbar div[data-widget_type="widget"]').draggable({
        connectToSortable: '.autolead-widget-wrapper',
        opacity: 0.5,
        helper: 'clone',
        cursorAt: {top: 19, left: 22},
        'revert': 'invalid'
    });


    /**
     * Fires when textare input choice and ul are dropped
     */
    jQuery('div[data-widget_type="textarea"],div[data-widget_type="textinput"],div[data-widget_type="choice"],div[data-widget_type="list"]').draggable({
        connectToSortable: '.autolead-answer',
        opacity: 0.5,
        helper: 'clone',
        cursorAt: {top: 19, left: 22},
        'revert': 'invalid'
    });


    /**
     * Fires when the list items are dragged on to the ul
     */
    jQuery('.autolead-toolbar div[data-widget_type="list_item"]').draggable({
        connectToSortable: '.autolead-list',
        opacity: 0.5,
        helper: function() {
            return jQuery('[data-widget_type="list_item"]').clone().addClass('list-dropper-helper');
        },
        cursorAt: {top: 19, left: 22},
        'revert': 'invalid'
    });


    /**
     * Make recycle bin icon droppable
     */
    jQuery('.autolead-bin').droppable({
        drop: al_builder_on_widget_delete,
        accept: '.autolead-widget, .autolead-list li',
        revert: 'invalid',
        hoverClass: 'al-remove-hightlight'
    });


    /**
     * Makes the right side main wrapper area sortable
     */
    jQuery('.autolead-widget-wrapper').sortable({
        revert: true,
        distance: 10,
        placeholder: 'widget-placement-highlight',
        cursorAt: {top: 19, left: 22},
        helper: function() {
            return jQuery('[data-widget_type="widget"]').clone().addClass('widget-dropper-helper');
        },
        opacity: 0.7,
        receive: al_builder_on_widget_receive,
        stop: al_builder_on_widget_stop
    });

    /**
     * Init the answer area
     */
    al_builder_answer_init(jQuery('.autolead-answer'));

    /**
     * Init the list items so that it's sortable
     */
    al_builder_list_init(jQuery('.autolead-list'));

    /**
     * Enables the tooltip for the toolbar icons on the left
     */
    jQuery('.autolead-toolbar .btn').tooltip({
        container: 'body'
    });


    /**
     * Makes the toolbar floatable on the left when height increases
     */
    jQuery('.autolead-toolbar').stickyfloat({
        duration: 400,
        startOffset: 50
    });


    /**
     * Initialize Alertify
     */
    jQuery(document).on('click', '[data-editable="yes"]', function(e) {
        e.preventDefault();
        var $this = jQuery(this);
        var ele = $this[0].tagName.toString().toLowerCase();
        var existing_val, use_val;
        switch (ele) {
            /**
             * If this is text
             */
            case 'input':
            case 'textarea':
                existing_val = $this.val();
                use_val = true;
                break;

            default:
                existing_val = $this.text();
                break;

        }
        alertify.prompt("Edit Text", function(e, str) {
            if (e) {
                if (use_val) {
                    $this.val(str);
                } else {
                    $this.text(str);
                }
            } else {
                $this.focus();
            }
        }, existing_val);
    });


    /**
     * Send Ajax Data to update the builder 
     */
    jQuery('.update-builder-wrapper .btn').on('click', function(e) {
        e.preventDefault();
        var $this = jQuery(this);
        var builder_data = jQuery('.autolead-widget-wrapper').html();

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'autolead_save_builder_meta',
                nonce: autolead_vars.nonce,
                post_id: autolead_vars.post_id,
                builder_data: builder_data
            },
            beforeSend: function() {
                $this.next('.al-loader').remove();
                $this.next('#message').remove();
                $this.after('<img src="' + autolead_vars.ajax_loader + '" class="al-loader" />')
            },
            success: function(response) {
                if (response.success) {
                    $this.next('.al-loader').remove();
                    $this.after('<div id="message" class="updated al-message">' + response.success + '</div>');
                    setTimeout(function() {
                        $this.next('#message').fadeOut(500, function() {
                            jQuery(this).remove();
                        });
                    }, 2000);
                }
            }
        });
    });


    /**
     * Makes the li active when clicked
     */
    jQuery(document).on('click', '.autolead-list span.icon', function() {
        var $this = jQuery(this);
        $this.closest('ul').find('li').removeClass('active').addClass('inactive');
        $this.closest('li').addClass('active');
    });


    /**
     * 
     * @type Boolean|Boolean|Boolean|Boolean
     */
    jQuery('.al-context-dropdown').on('change', function() {
        var $this = jQuery(this);
        var context_wrap = jQuery('[data-context=' + $this.attr('name') + '_' + $this.val() + ']');
        if (context_wrap.length) {
            var data_group = context_wrap.data('group');
            jQuery('[data-group="' + data_group + '"]').not(context_wrap).addClass('hidden');
            context_wrap.removeClass('hidden');
        } else {
            jQuery('[data-context^=' + $this.attr('name') + ']').addClass('hidden');
        }
    });


    /**
     * Open up the active context wrapper for after survey option
     */
    jQuery('.al-context-dropdown').each(function() {

        var $parent = jQuery(this);
        var $parent_name = $parent.attr('name');

        /**
         * Iterate over the context openers to open and close
         * the appropriate ones
         */
        jQuery('[data-group="' + $parent_name + '"]').each(function() {
            var $this = jQuery(this);
            var context = $this.data('context');
            if (context == $parent_name + '_' + $parent.val()) {
                $this.removeClass('hidden');
            } else {
                $this.addClass('hidden');
            }
        });

    });


});


/**
 * Global variables
 * @type Boolean
 */
var al_new_received = false; // Used in drop event handlers


/**
 * Event handler, called when a new widget is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_widget_stop(event, ui) {

    if (!al_new_received) {
        return; // Global variable, set in al_builder_on_widget_receive
    }

    al_new_received = false; // Reset for next time

    // i.e. extract "textarea" from "widget-tool-textarea"
    var widget_type = ui.item.data('widget_type');

    switch (widget_type)
    {
        case 'widget':
            al_builder_on_new_widget(event, ui);
            break;

        case 'textarea':
            al_builder_on_new_textarea(event, ui);
            break;

        case 'textinput':
            al_builder_on_new_input(event, ui);
            break;

        case 'choice':
            al_builder_on_new_choice(event, ui);
            break;

        case 'list':
            al_builder_on_new_list(event, ui);
            break;

        case 'list_item':
            al_builder_on_new_list_item(event, ui);
            break;

        default:
    }

}


/**
 * Event handler, called from al_builder_on_widget_stop
 * When new widget is dropped
 * 
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_widget(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .autolead-widget').first().clone();
    ui.item.replaceWith($elem);
    var $answer = $elem.find('.autolead-answer');
    $answer.css('min-height', '35px');
    al_builder_answer_init($answer);
}

/**
 * Event handler, called from al_builder_on_widget_stop
 * When new textarea is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_textarea(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .autolead-textarea').first().clone();
    ui.item.replaceWith($elem);
    /**
     * Removes all the siblings from the widget answer area afer drop
     */
    $elem.siblings().remove();
    $elem.closest('.autolead-answer').attr('data-type', 'textarea');
}


/**
 * Event handler, called from al_builder_on_widget_stop
 * When new input is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_input(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .autolead-input').first().clone();
    ui.item.replaceWith($elem);
    $elem.siblings().remove();
    $elem.closest('.autolead-answer').attr('data-type', 'input');
}

/**
 * Event handler, called from al_builder_on_widget_stop
 * When new list is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_list(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .autolead-list').first().clone();
    ui.item.replaceWith($elem);
    al_builder_list_init($elem);
    $elem.siblings().remove();
    $elem.closest('.autolead-answer').attr('data-type', 'list');
}


/**
 * Event handler, called from al_builder_on_widget_stop
 * When new list item is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_list_item(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .autolead-list li:first-child').first().clone();
    ui.item.replaceWith($elem);
    $elem.closest('ul').find('li').removeClass('active').addClass('inactive');
    $elem.siblings('li:first').addClass('active');
}


/**
 * Event handler, called from al_builder_on_widget_stop
 * When new Yes/No swipe button is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_new_choice(event, ui) {
    var $elem = jQuery('.autolead-hidden-stock .choice-selector').first().clone();
    ui.item.replaceWith($elem);
    $elem.siblings().remove();
    $elem.closest('.autolead-answer').attr('data-type', 'choice');
}

/**
 * Initialize answer area
 * @param {type} $elem
 * @returns {undefined}
 */
function al_builder_answer_init($elem) {
    $elem.css('min-height', '50px')
            .sortable({
        revert: true,
        distance: 10,
        placeholder: 'al-highlight',
        receive: al_builder_on_widget_receive,
        stop: al_builder_on_widget_stop
    });
    $elem.siblings('button').click(function(ev) {
        ev.preventDefault();
    });
}


/**
 * Initialize list area
 * @param {type} $elem
 * @returns {undefined}
 */
function al_builder_list_init($elem) {
    $elem.sortable({
        revert: true,
        distance: 10,
        placeholder: 'al-list-highlight',
        helper: function() {
            return jQuery('[data-widget_type="list_item"]').clone().addClass('list-dropper-helper');
        },
        receive: al_builder_on_widget_receive,
        stop: al_builder_on_widget_stop
    });
}


/**
 * Event handler, called when a new widget is dropped
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_widget_receive(event, ui) {
    /**
     * Global variable
     * It is then used in al_builder_on_widget_stop
     */
    al_new_received = true;
}

/**
 * Fires when a widget is deleted
 * @param {type} event
 * @param {type} ui
 * @returns {undefined}
 */
function al_builder_on_widget_delete(event, ui) {
    ui.helper.hide();
    ui.draggable.remove();
}


