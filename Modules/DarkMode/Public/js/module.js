$('document').ready(function () {
    
    $("#dm-switch input:first").on('change', function () {
        if ($(this).is(':checked')) {
            // Enable dark mode.
            dmEnable();
        } else {
            // Disable dark mode.
            dmDisable();
        }
    });

    var darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    if (darkModeMediaQuery.matches && !$("#dm-switch input:first").is(':checked')) {
        if (!$("#dm-switch input:first").is(':checked')) {
            $("#dm-switch input:first").prop("checked", true);
        }
        dmEnable();
    }

    darkModeMediaQuery.addListener((e) => {
        var darkModeOn = e.matches;
        if (darkModeOn) {
            if (!$("#dm-switch input:first").is(':checked')) {
                $("#dm-switch input:first").prop("checked", true);
            }
            dmEnable();
        } else {
            if ($("#dm-switch input:first").is(':checked')) {
                $("#dm-switch input:first").prop("checked", false);
            }
            dmDisable();
        }
    });
});

function dmEnable()
{
    setCookie('dm_enabled', 1);
    dmSwitchMode(true);
}

function dmDisable()
{
    setCookie('dm_enabled', 0);
    dmSwitchMode(false);
}

function dmSwitchMode(on)
{
    var body = $('body');
    
    if (on) {
        body.addClass('dm');
    } else {
        body.removeClass('dm');
    }
}
