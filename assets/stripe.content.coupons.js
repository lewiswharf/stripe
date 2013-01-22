jQuery(document).ready(function() {
    // Pickers
    jQuery('#duration').find('select.picker').symphonyPickable({
        content: '#duration',
        pickables: '.pickable'
    });

    jQuery('#type').find('select.picker').symphonyPickable({
        content: '#type',
        pickables: '.pickable'
    });
});