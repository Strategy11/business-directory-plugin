jQuery(function($) {

    var wpbdp = window.wpbdp || {};
    wpbdp.submit_listing = wpbdp.submit_listing || {};

    // Fee_Selection_Helper {{
    wpbdp.submit_listing.Fee_Selection_Helper = function( $submit ) {
        this.reset();
    };
    $.extend( wpbdp.submit_listing.Fee_Selection_Helper.prototype, {
        reset: function() {
            this.field = $( '.wpbdp-form-field-association-category select' );
            // this.field.select2();

            this.is_multiple = this.field.prop( 'multiple' );
            this.plans = $( '.wpbdp-plan-selection-list .wpbdp-plan' );

            this.selected_categories = [];
            this.available_plans = this.plans.map(function() {
                return $(this).data('id');
            }).get();

            this.field.change( $.proxy( this.categories_changed, this ) );
            this.maybe_limit_category_options();
            this.field.trigger('change');
        },

        categories_changed: function() {
            this.selected_categories = this.field.val();
            if ( ! this.selected_categories )
                this.selected_categories = [];

            if ( ! $.isArray( this.selected_categories ) )
                this.selected_categories = [this.selected_categories];

            if ( ! this.selected_categories ) {
                this.selected_categories = [];
            }

            this.update_plan_list();
            this.update_plan_prices();
            this.maybe_limit_category_options();

            if ( 0 == this.selected_categories.length ) {
                this.plans.find( 'input[name="listing_plan"]' ).prop( {
                    'disabled': 0 == this.selected_categories.length,
                    'checked': false 
                } );
            } else {
                this.plans.find( 'input[name="listing_plan"]' ).prop( 'disabled', false );
            }

            // Workaround for https://github.com/select2/select2/issues/3992.
            var self = this;
            setTimeout(function() {
                self.field.select2();
            });
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
                this.field.find('option').prop( 'disabled', false );
            } else {
                this.field.find('option').each(function(i, v) {
                    $(this).prop( 'disabled', -1 == $.inArray( $(this).val(), cats ) );
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

                $plan.find( '.wpbdp-plan-price-amount' ).text( price ? $plan.data( 'amount-format' ).replace( '[amount]', price.toFixed(2) ) : $plan.data( 'free-text' ) );
            } );
        }
    });
    // }}

    wpbdp.submit_listing.Handler = function( $submit ) {
        this.$submit = $submit;
        this.$form = this.$submit.find( 'form' );
        this.$sections = this.$submit.find( '.wpbdp-submit-listing-section' );

        this.listing_id = this.$form.find( 'input[name="listing_id"]' ).val();
        this.ajax_url = this.$form.attr( 'data-ajax-url' );
        this.doing_ajax = false;

        this.setup_section_headers();
        this.plan_handling();

        var self = this;
        this.$form.on( 'click', ':reset', function( e ) {
            e.preventDefault();
            self.$form.find('input[name="save_listing"]').val( '' );
            self.$form.find('input[name="reset"]').val( 'reset' );
            self.$form.submit();
        } );

        // Create account form.
        $( '#wpbdp-submit-listing' ).on( 'change', '#wpbdp-submit-listing-create_account', function( e ) {
            $( '#wpbdp-submit-listing-account-details' ).toggle();
        } );
    };
    $.extend( wpbdp.submit_listing.Handler.prototype, {
        ajax: function( data, callback ) {
            if ( this.doing_ajax ) {
                alert( 'Please wait a moment!' );
                return;
            }

            this.doing_ajax = true;
            var self = this;

            $.post( this.ajax_url, data, function( res ) {
                if ( ! res.success ) {
                    alert('Something went wrong!');
                    return;
                }

                self.doing_ajax = false;
                callback.call( self, res.data );
            }, 'json' );
        },

        setup_section_headers: function() {
            this.$sections.find( '.wpbdp-submit-listing-section-header' ).click(function() {
                var $section = $( this ).parent( '.wpbdp-submit-listing-section' );
                $section.toggleClass( 'collapsed' );
            });
        },

        plan_handling: function() {
            this.fee_helper = new wpbdp.submit_listing.Fee_Selection_Helper( this.$submit );

            var self = this;
            this.$submit.on( 'change, click', 'input[name="listing_plan"]', function() {
                var data = self.$form.serialize();
                data += '&action=wpbdp_ajax&handler=submit_listing__sections';

                self.ajax( data, function( res ) {
                    self.refresh( res );
                } );
            } );

            this.$submit.on( 'click', '#change-plan-link a', function(e) {
                e.preventDefault();

                var data = self.$form.serialize();
                data += '&action=wpbdp_ajax&handler=submit_listing__reset_plan';

                self.ajax( data, function( res ) {
                    self.refresh( res );
                } );
            }) ;
        },

        refresh: function(data) {
            var sections = data.sections;
            var messages = data.messages;

            var current_sections = this.$form.find( '.wpbdp-submit-listing-section' );
            var new_sections = sections;

            var self = this;

            // Update sections.
            $.each( new_sections, function( section_id, section_details ) {
                var $section = current_sections.filter( '[data-section-id="' + section_id + '"]' );
                var $new_html = $( section_details.html );

                $section.attr( 'class', $new_html.attr( 'class' ) );
                $section.find( '.wpbdp-submit-listing-section-content' ).fadeOut( 'fast', function() {
                    $( this ).replaceWith( $new_html.find( '.wpbdp-submit-listing-section-content' ) );
                    self.fee_helper.reset();
                } );
            } );
        }

    });

    var $submit = $( '#wpbdp-submit-listing' );
    if ( $submit.length > 0 )
        var x = new wpbdp.submit_listing.Handler( $submit );

});
