$(document).ready(function () {

    $('.new-custom-fields-save').click(function () {
        var text=[]
        var options=document.getElementsByClassName('options')
        for (let i=0;i<options.length;i++){
            if (options[i].value!='') {
                text.push({option: options[i].value})
            }
        }
        $.ajax({
            url: $('#form_submit').attr('action'),
            method: 'post',
            data: {
                _token: $('[name=_token]').val(),
                name: $('[name=name]').val(),
                type: $('[name=type]').val(),
                required: $('[name=req]').prop('checked'),
                text: text
            },
            success: function (result) {
                if (result.status=='successful') {
                    location.reload()
                }
            },
            errors: function (result) {
                console.log(result)
            }
        })

    })

    $('.user_custom_field').change(()=>{

        var text=[]
        for (var i=0; i<$('.user_custom_field').length; i++){
            var value=document.getElementsByClassName('user_custom_field')[i].value
            var name=document.getElementsByClassName('user_custom_field')[i].name

            text.push({name:name,value:value})
        }

        $.ajax({
            url: $('[name=url]').val(),
            method: 'post',
            data: {
                _token: $('[name=_token]').val(),
                data: text
            },
            success: function (result) {
                console.log(result)
            },
            errors: function (result) {
                console.log(result)
            }
        })

    })

    $('.change_custom_fields').click(function(){
        var id=$(this).attr('data-id')
        var name=$('[name=change_name_'+id+']').val()
        var required=$('[name=change_reg_'+id+']').prop('checked')
        $.ajax({
            url: $('[name=change]').val(),
            method: 'post',
            data: {
                _token: $('[name=_token]').val(),
                id: id,
                name: name,
                required: required
            },
            success: function (result) {
                if (result.status=='successful') {
                    location.reload()
                }
            },
            errors: function (result) {
                console.log(result)
            }
        })
    })
})
