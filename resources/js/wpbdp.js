jQuery(document).ready(function($){

    /* search form */
    $('#wpbdp-search-form .search-filter .header input[type="checkbox"]').change(function(e){
        var $filter = $(this).parents('.search-filter');
        $filter.toggleClass('expanded');
    });

    $('#wpbdp-search-form .search-filter .header input[type="checkbox"]:checked').each(function(i,v){
        $(v).change();
    });

    $('#wpbdp-search-form .search-filter .header input[type="checkbox"]:not(:checked)').each(function(i,v){
        var $filter = $(v).parents('.search-filter');
        $('.options input[type="checkbox"]', $filter).removeAttr('checked');
        $('.options select option', $filter).removeAttr('selected');
        $('.options input[type="text"]', $filter).val('');
    });

});