
/**
* Constructor
* @param selector string (required), jQuery selector of the form which needs to be intercepted
* @param message string (required), message displayed on the output box
* @param target string (required), jQuery selector corresponding to the HTML element that will be after the output box
* @param event string (optional), form event to listen. Possible values: submit, live. Default: submit.
*/
function IscBlockForm(selector, message, target, event)
{
    // Private attributes
    var instance = this;
    if ('undefined' == typeof(IscBlockForm.IFB_counter)) {
        IscBlockForm.IFB_counter = 0;
    }
    var instance_id = IscBlockForm.IFB_counter;

    IscBlockForm.IFB_counter++;

    if ('string' != typeof(selector)) {
        throw 'Param 1 of IscBlockForm must be a string';
    }
    var main_selector = selector;

    if ('string' != typeof(message)) {
        throw 'Param 2 of IscBlockForm must be a string';
    }
    var text = message;

    if ('string' != typeof(target)) {
        throw 'Param 3 of IscBlockForm must be a string';
    }
    var target_selector = target;

    var l_event = 'submit';
    if ('live' == event) {
        l_event = 'live';
    }
    var fields = {};
    var main_filter = undefined;

    // Private methods

    /**
    * Scroll the window to the warning box
    */
    var jump_to = function(elem) {
        pos = elem.offset();
        jQuery(document).scrollTop(pos.top);
        jQuery(document).scrollLeft(pos.left);
    }

    /**
    * Display the warning box
    */
    var show_box = function() {
        var box_id = 'IBF-' + instance_id;
        if (0 < jQuery('#' + box_id).length)
            jQuery('#' + box_id).remove();
        d = jQuery('<div />').attr({id : box_id}).css({
            border : '1px solid #eca',
            backgroundColor : '#feb',
            borderRadius: '2px',
            height : '0%',
            opacity: 0,
            color: '#f30',
        });
        m = jQuery('<p />').text(text).css({
            padding : '3px 5px',
            margin: '5px',
            fontWeight: 'bold',
        });
        jQuery(target_selector).before(d.append(m));

        if ('live' != l_event)
            jump_to(d);

        d.animate(
            {opacity: 1, height: '100%'},
            1500,
            'swing'
        );
    }

    /**
    * remove the warning box
    */
    var remove_box = function() {
        var box_id = 'IBF-' + instance_id;
        d = jQuery('#' + box_id);
        if (0 < d.length) {
            d.fadeOut(1500, function(){
                d.remove();
            });
        }
    }

    /**
    * Update value of fields
    */
    var update_fields = function() {
        for (var id in fields) {
            elem = jQuery(fields[id].selector);
            if(elem.length == 0) continue; // check if element exists
            tagname = elem.prop('tagName').toLowerCase();
            switch (tagname) {
                case 'input' :
                    type = elem.attr('type');
                    switch (type) {
                        case 'text':
                            fields[id].type = 'text';
                            fields[id].value = elem.val();
                            break;
                        case 'checkbox':
                            fields[id].type = 'checkbox';
                            fields[id].value = elem.prop('checked');
                            break;
                        default :
                            break;
                    }
                    break;

                case 'select' :
                    break;

                case 'textarea' :
                    break;

            }
        }
    }

    // Public methods

    /**
    * Check the form with the filter
    */
    this.is_valid = function() {
        update_fields();
        return main_filter(fields);
    }


    /**
    * Register a field to be tested.
    * @param field_id string (required), unique id for this registred field
    * @param selector string (required), jQuery selector corresponding to the field. Id or name attributes are good.
    */
    this.register_field = function(field_id, selector) {
        if ('string' != typeof(selector)) {
            throw 'Param 1 of IscBlockForm.addfield must be a string';
        }
        if ('undefined' != typeof(fields[field_id])) {
            throw 'duplicated id ' + field_id;
        }
        if (1 < jQuery(selector).length) {
            throw 'the jQuery selector ' + selector + ' match more than one element.';
        }
        fields[field_id] =
        {
            'selector'      : selector,
            'value'         : undefined,
            'type'          : undefined
        }
    }

    /**
    * Set the main filter function.
    * @param main_filter_cb callback (required), when the function returns false, the submission is intercepted and the output box displayed.
    * This callback accepts one parameter: the fields object.
    */
    this.filter = function(main_filter_cb) {
        main_filter = main_filter_cb;
    };

    /**
    * Attach the event defined in arguments
    */
    this.attach = function() {
        switch (l_event) {
            case 'submit' :
                jQuery(main_selector).live('submit', function(){
                    if (instance.is_valid()) {
                        return true;
                    } else {
                        show_box();
                        return false;
                    }
                });
                break;
            case 'live' :
                for (var id in fields) {
                    jQuery(fields[id].selector).live('change', function(){
                        if (instance.is_valid()) {
                            remove_box();
                        } else {
                            show_box();
                        }
                    });
                }
                break;
        }
    }
}

/**
* The main program
*/
jQuery(function(){
    if (isc_data.warning_nosource) {
        classic_form = new IscBlockForm('#post', isc_data.block_form_message, '.compat-attachment-fields', 'submit');
        classic_form.register_field('source', '#post .compat-field-isc_image_source input');
        classic_form.register_field('own', '#post .compat-field-isc_image_source_own input');
        classic_form.filter(function(data){
            if ('' == data['source'].value && false == data['own'].value) {
                return false;
            } else {
                return true;
            }
        });
        classic_form.attach();

        live_form = new IscBlockForm('.compat-item', isc_data.block_form_message, '.compat-attachment-fields', 'live');
        live_form.register_field('source', '.compat-item .compat-field-isc_image_source input');
        live_form.register_field('own', '.compat-item .compat-field-isc_image_source_own input');
        live_form.filter(function(data){
            if ('' == data['source'].value && false == data['own'].value) {
                return false;
            } else {
                return true;
            }
        });
        live_form.attach();
    }
});
