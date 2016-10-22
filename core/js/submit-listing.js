jQuery(function($) {
    var submit_listing = wpbdp.submit_listing = wpbdp.submit_listing || {};

    // Fee_Selection_Helper {{
    submit_listing.Fee_Selection_Helper = function() {
        this.field = $( '.wpbdp-form-field-association-category select' );
        this.is_multiple = this.field.prop( 'multiple' );
        this.plans = $( '.wpbdp-plan-selection-list .wpbdp-plan' );

        this.selected_categories = [];
        this.available_plans = this.plans.map(function() {
            return $(this).data('id');
        }).get();

        this.field.change( $.proxy( this.categories_changed, this ) );
        this.maybe_limit_category_options();
    };
    $.extend(submit_listing.Fee_Selection_Helper.prototype, {
        categories_changed: function() {
            this.selected_categories = this.field.val();
            this.update_plan_list();
            this.update_plan_prices();
            this.maybe_limit_category_options();
        },

        maybe_limit_category_options: function() {
            var all_cats = false;
            var cats = [];
            var self = this;

            $.each(this.available_plans, function(i, v) {
                if ( all_cats )
                    return;

                var plan_cats = self.plans.filter('[data-id="' + v + '"]').data('categories');

                if ( 'all' == plan_cats ) {
                    all_cats = true;
                } else {
                    cats = $.unique( cats.concat( plan_cats.toString().split( ',' ) ) );
                }
            });

            if ( all_cats ) {
                this.field.find('option').prop( 'disabled', false ); return;
            } else {
                this.field.find('option').each(function(i, v) {
                    if ( -1 == $.inArray( $(this).val(), cats ) )
                        $(this).prop( 'disabled', true );
                });
            }
        },

        update_plan_list: function() {
            var self = this;
            var plans = [];

            // Recompute available plans depending on category selection.
            $.each( this.plans, function( i, v ) {
                var $plan = $( v );
                var plan_cats = $plan.data('categories').toString().split(',');
                var plan_supports_selection = true;

                if ( 'all' != plan_cats && self.selected_categories ) {
                    $.each( self.selected_categories, function( j, c ) {
                        if ( ! plan_supports_selection )
                            return;

                        if ( -1 == $.inArray( c, plan_cats ) )
                            plan_supports_selection = false;
                    } );
                }

                if ( plan_supports_selection ) {
                    plans.push( $plan.data('id') );
                    $plan.show();
                } else {
                    $plan.hide();
                }
            } );

            self.available_plans = plans;
        },

        update_plan_prices: function() {
            var self = this;

            $.each( self.available_plans, function( i, plan_id ) {
                var $plan = self.plans.filter('[data-id="' + plan_id + '"]');
                var pricing = $plan.data('pricing-details');
                var price = null;

                switch ( $plan.data( 'pricing-model' ) ) {
                    case 'variable':
                        price = 0.0;

                        $.each( self.selected_categories, function( j, cat_id ) {
                            price += parseFloat(pricing[cat_id]);
                        } );
                        break;
                    case 'extra':
                        price = $plan.data( 'amount' ) + ( pricing.extra * self.selected_categories.length );
                        break;
                    case 'flat':
                    default:
                        price = $plan.data( 'amount' );
                        break;
                }

                $plan.find( '.wpbdp-plan-price-amount' ).text( price );
            } );
        }
    });
    // }}

    $( '#wpbdp-submit-listing .wpbdp-submit-listing-section-header' ).click( function() {
        var $section = $( this ).parent( '.wpbdp-submit-listing-section' );
        $section.toggleClass( 'collapsed' );
    } );

    if ( $( '#wpbdp-submit-listing .wpbdp-plan-selection-list' ).length > 0 ) {
        var x = new submit_listing.Fee_Selection_Helper();

        $( 'input[name="listing_plan"]' ).change(function() {
            var $form = $('#wpbdp-submit-listing form');
            var data = $form.serialize();
            data += '&action=wpbdp_ajax&handler=submit_listing__sections';

            // TODO: add status indicator (throbber?)

            $.post( $form.attr( 'data-ajax-url' ), data, function( res ) {
                if ( ! res.success ) {
                    alert( 'Something went wrong!' );
                    return;
                }

                var current_sections = $form.find( '.wpbdp-submit-listing-section' );
                var new_sections = res.data.sections;

                // Update sections.
                $.each( new_sections, function( section_id, section_details ) {
                    var $section = current_sections.filter( '[data-section-id="' + section_id + '"]' );
                    var $new_html = $( section_details.html );

                    $section.attr( 'class', $new_html.attr( 'class' ) );
                    $section.find( '.wpbdp-submit-listing-section-content' ).fadeOut( 'fast', function() {
                        $( this ).replaceWith( $new_html.find( '.wpbdp-submit-listing-section-content' ) );
                    } );
                } );

            }, 'json' );

        });
    }

    $( '#wpbdp-submit-page.step-images #wpbdp-uploaded-images' ).sortable({
        placeholder: 'wpbdp-image-draggable-highlight',
        update: function( event, ui ) {
            var sorted = $( '#wpbdp-uploaded-images' ).sortable( 'toArray', { attribute: "data-imageid" } );
            var no_images = sorted.length;

            $.each( sorted, function( i, v ) {
                $( 'input[name="images_meta[' + v + '][order]"]' ).val( no_images - i );
            } );
        }
    });
});
