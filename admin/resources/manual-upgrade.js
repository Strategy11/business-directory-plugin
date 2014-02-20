jQuery(function($) {
    var $startButton = $('.wpbdp-page-manual-upgrade a.start-upgrade');
    var $pauseButton = $('.wpbdp-page-manual-upgrade a.pause-upgrade');
    var $progressArea = $('textarea#manual-upgrade-progress');
    var inProgress = false;

    var makeProgress = function() {
        if (!inProgress)
            return;

        var data = { action: 'wpbdp-manual-upgrade' };
        $.get(ajaxurl, data, function(response) {
            var currentText = $progressArea.val();
            var newLine = (response.ok ? "*" : "!") + " " + response.statusText;

            $progressArea.val(currentText + newLine + "\n");

            if (response.done) {
                alert('Done!');
            } else {
                makeProgress();
            }
        }, 'json');
    };
    
    $startButton.click(function(e) {
        e.preventDefault();

        if (inProgress)
            return;

        inProgress = true;
        makeProgress();
    });

    $pauseButton.click(function(e) {
        e.preventDefault();
        inProgress = false;
    });

});