jQuery(function($) {
    var progressBar = new WPBDP_Admin.ProgressBar($('.step-2 .export-progress'));

    var exportInProgress = false;
    var cancelExport = false;

    var advanceExport = function(state) {
        if (!exportInProgress)
            return;
            
        if (cancelExport) {
            exportInProgress = false
            cancelExport = false;
            
            $('.step-2').fadeOut(function() {
                $('.canceled-export').fadeIn();
            });
            
            $.ajax(ajaxurl, {
                data: { 'action': 'wpbdp-csv-export', 'state': state, 'cleanup': 1 },
                type: 'POST',
                dataType: 'json',
                success: function(res) {
                }
            });
            return;
        }
            
        $.ajax(ajaxurl, {
            data: { 'action': 'wpbdp-csv-export', 'state': state },
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.error) {
                    exportInProgress = false;
                    alert(res.error);
                    return;
                }

                $('.step-2 .listings').text(res.exported  + ' / ' + res.count);
                $('.step-2 .size').text(res.filesize);
                progressBar.set(res.exported, res.count);
                
                if (res.isDone) {
                    exportInProgress = false;
                    
                    $('.step-2').fadeOut(function() {
                        $('.step-3 .download-link a').attr('href', res.fileurl);
                        $('.step-3 .download-link a .filename').text(res.filename);
                        $('.step-3 .download-link a .filesize').text(res.filesize);                        
                        
                        $('.step-3').fadeIn(function() {
                            $('.step-3 .cleanup-link').hide();
                        })
                    } );

                } else {                
                    advanceExport(res.state);
                }
            }
        });
    };
    
    
    $('form#wpbdp-csv-export-form').submit(function(e) {
        e.preventDefault();
        
        var data = $(this).serialize() + '&action=wpbdp-csv-export';
        $.ajax(ajaxurl, {
           data: data,
           type: 'POST',
           dataType: 'json',
           success: function(res) {
               if (res.error) {
                   alert(res.error);
                   return;
               }
            
               $('.step-1').fadeOut(function(){
                   exportInProgress = true;
                   $('.step-2 .listings').text('0 / ' + res.count);
                   $('.step-2 .size').text('0 KB');
                   
                   $('.step-2').fadeIn(function() {
                       advanceExport(res.state);
                   });
               });
           }
        });
    });
    
    $('a.cancel-import').click(function(e) {
        e.preventDefault();
        cancelExport = true;
    });
    
    $('.step-3 .download-link a').click(function(e) {
        $('.step-3 .cleanup-link').fadeIn(); 
    });
});