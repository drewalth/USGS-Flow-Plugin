// scripts to toggle view options for flow plugin
function stateSorter() {
    var
        btn = $('.althageBtn'),
        co = $('tr:has(td:contains("CO"))'),
        az = $('tr:has(td:contains("AZ"))'),
        nm = $('tr:has(td:contains("NM"))'),
        ut = $('tr:has(td:contains("UT"))'),
        coToggle = $('.coToggle'),
        azToggle = $('.azToggle'),
        nmToggle = $('.nmToggle'),
        utToggle = $('.utToggle'),
        reset = $('.filterReset');

    // this code works. show and hide appropraite tr
    // this needs to be DRYer...

    coToggle.on('click', function(event) {
        event.preventDefault();
        co.show();
        ut.hide();
        nm.hide();
        az.hide();
    });


    azToggle.on('click', function(event) {
        event.preventDefault();
        az.show();
        co.hide();
        ut.hide();
        nm.hide();
    });
    nmToggle.on('click', function(event) {
        event.preventDefault();
        nm.show();
        ut.hide();
        co.hide();
        az.hide();
    });

    utToggle.on('click', function(event) {
        event.preventDefault();
        ut.show();
        co.hide();
        az.hide();
        nm.hide();
    });
    reset.on('click', function(event) {
        event.preventDefault();
        az.show();
        co.show();
        nm.show();
        ut.show();
    });


    btn.click(function() {

        $('a').removeClass("toggleActive");
        // add class to the one we clicked
        $(this).addClass("toggleActive");
    });

    // add active class to all toggle
}

stateSorter();
// Accessibility Mode toggle


$(document).ready(function() {

    var $tooLow = $('.tooLow'),
        $running = $('.running'),
        $tooHigh = $('.tooHigh'),
        $accessibilityToggle = $('.accessibilityToggle');

    $accessibilityToggle.on('click', function() {
        $tooLow.toggleClass('tooLowAccess');
        $running.toggleClass('runningAccess');
        $tooHigh.toggleClass('tooHighAccess');

    });

});