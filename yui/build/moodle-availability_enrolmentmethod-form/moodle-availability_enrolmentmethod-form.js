YUI.add('moodle-availability_enrolmentmethod-form', function (Y, NAME) {

/**
 * JavaScript for form editing group conditions.
 *
 * @module moodle-availability_enrolmentmethod-form
 */
M.availability_enrolmentmethod = M.availability_enrolmentmethod || {};

/**
 * @class M.availability_enrolmentmethod.form
 * @extends M.core_availability.plugin
 */
M.availability_enrolmentmethod.form = Y.Object(M.core_availability.plugin);

/**
 * Groups available for selection (alphabetical order).
 *
 * @property groups
 * @type Array
 */
M.availability_enrolmentmethod.form.groups = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} groups Array of objects containing groupid => name
 */
M.availability_enrolmentmethod.form.initInner = function(groups) {
    this.groups = groups;
};

M.availability_enrolmentmethod.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<label><span class="pr-3">' + M.util.get_string('title', 'availability_enrolmentmethod') + '</span> ' +
            '<span class="availability-group">' +
            '<select name="id" class="custom-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.groups.length; i++) {
        var group = this.groups[i];
        // String has already been escaped using format_string.
        html += '<option value="' + group.id + '">' + group.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined &&
                node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', '' + json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'any');
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_enrolmentmethod.form.addedEvents) {
        M.availability_enrolmentmethod.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_enrolmentmethod select');
    }

    return node;
};

M.availability_enrolmentmethod.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = 'choose';
    } else if (selected !== 'any') {
        value.id = parseInt(selected, 10);
    }
};

M.availability_enrolmentmethod.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check group item id.
    if (value.id && value.id === 'choose') {
        errors.push('availability_enrolmentmethod:error_selectenrolmentmethod');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
