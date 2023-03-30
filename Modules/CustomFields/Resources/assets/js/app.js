var tag=()=>{
    return `<div class="col-md-12 draggable_content"><input class="form-control col-md-11 options" type="text" style="display: inline-block !important;"><span class="col-md-1"><a href="javascript:void(0)" class="delete_draggable_content"><i class="fa fa-times"></i></a></span></div>`
}

$('[name=type]').click(()=>{
    if ($('[name=type]').val()=='dropdown') {
        $('#options').show()
    }
    else {
        $('#options').hide()
    }
})
$('#options #add').click(()=>{
    $('#options #draggable_content').append(tag)
    f()
})
function f() {
    $('.delete_draggable_content').click((close)=>{
        if ($('.options').length>1) {
            $(close.target).parents('.draggable_content').remove()
        }
    })
}

$( ".custom-fields-view-content" ).click(function () {
    if ( $( this ).parents(".custom-fields-view").children(".custom-fields-view-details").is( ":hidden" ) ) {
        $(".custom-fields-view").children(".custom-fields-view-details").slideUp("hide")
        $(".custom-fields-view").css('background','#ffffff')
        $( this ).parents(".custom-fields-view").children(".custom-fields-view-details").slideDown( "slow" );
        $( this ).parents(".custom-fields-view").css('background','#f1f3f5')
    } else {
        $( this ).parents(".custom-fields-view").children(".custom-fields-view-details").slideUp("hide");
        $( this ).parents(".custom-fields-view").css('background','#ffffff')

    }
});