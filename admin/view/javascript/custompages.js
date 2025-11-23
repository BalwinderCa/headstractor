$(document).ready(function() {
    $('body').addClass('isenselabs-module');

    // Main navigation
    $('#mainTabs a:first').tab('show'); // Select first tab
    if (window.localStorage && window.localStorage['currentTab']) {
        $('#mainTabs a[href="' + window.localStorage['currentTab'] + '"]').tab('show');
    }
    $('#mainTabs a[data-toggle="tab"]').click(function() {
        if (window.localStorage) {
            window.localStorage['currentTab'] = $(this).attr('href');
        }
    });

    $('.langTabs').each(function() {
        $(this).find('a:first').tab('show');
    });
});
