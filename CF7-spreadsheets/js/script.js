jQuery(document).ready(function ($) {
    var file_string = '';

    var readURLE = function (input) {
        $('.CF7spreadsheets_status').html('<span class="CF7spreadsheets_loading"></span>');
        var reader = new FileReader();
        reader.fileNameSet = input.files[0].name;
        reader.onload = function (e, test) {
            $.getJSON(e.target.result).complete(function (json){
                $('.CF7spreadsheets_status').html('');
                file_string = JSON.stringify(json.responseJSON);
            });
        };
        reader.readAsDataURL(input.files[0]);
    };
    $('#CF7spreadsheets_api_file').change(function(){
        readURLE(this);
    });

    $('#CF7spreadsheets_option_submit').click(function () {
        var data = {};
        $(this).closest('form').serializeArray().map(function(x){data[x.name] = x.value;});
        data.action = 'CF7spreadsheets_update_ajax_options';
        $('.CF7spreadsheets_response').html('');
        if($('.CF7spreadsheets_error_ajax').length){
            $('.CF7spreadsheets_error_ajax').remove();
        }
        $.post(ajaxurl, data, function ($res) {
            try{
                var $res_json = JSON.parse($res);
                if($res_json.response == 'error'){
                    $.each($res_json.content, function (index, value){
                        $('[name="'+value.root+"["+value.title+']"]').after("<p class='CF7spreadsheets_error_ajax'>"+value.value+"</p>");
                    });
                }else{
                    if($res_json.response == 'success'){
                        $('.CF7spreadsheets_response').html($res_json.content);
                    }
                }
            }catch(e){
                $('.CF7spreadsheets_response').html("<p class='CF7spreadsheets_error_ajax'>"+e+"</p>");
            }
        });
    });

    $('#CF7spreadsheets_api_submit').click(function () {
        $('.CF7spreadsheets_response').html('');
        $.post(ajaxurl, {
            action: 'CF7spreadsheets_update_ajax_api',
            processData: false,
            contentType: false,
            CF7spreadsheets_api_file: file_string,
        }, function ($res) {
            $('.CF7spreadsheets_response').html($res);
        });
    });

    $('#CF7spreadsheets_output_submit').click(function () {
        var data = {};
        $('input[name="CF7spreadsheets_output_tags[]"]').serializeArray().map(function(x,i){data[i] = x.value;});
        $('.CF7spreadsheets_response').html('');
        $.post(ajaxurl, {
            action: 'CF7spreadsheets_update_ajax_output',
            CF7spreadsheets_post_id: $('#CF7spreadsheets_output_select').val(),
            CF7spreadsheets_output_tags: data,
        }, function ($res) {
            try{
                var $res_json = JSON.parse($res);
                if($res_json.response == 'error'){
                    $('.CF7spreadsheets_response').html("<div class='CF7spreadsheets_error_ajax'>"+$res_json.content+"</div>");
                }else{
                    if($res_json.response == 'success'){
                        $('.CF7spreadsheets_response').html("<div class='CF7spreadsheets_success_ajax'>"+$res_json.content+"</div>");
                    }
                }
            }catch(e){
                $('.CF7spreadsheets_response').html("<p class='CF7spreadsheets_error_ajax'>"+e+"</p>");
            }
        });
    });

    function get_form_tags(){
        if($('#CF7spreadsheets_output_select').length){
            $('#CF7spreadsheets_allowed_tags').html('');
            $('.CF7spreadsheets_table_cell').remove();
            $.post(ajaxurl, {
                action: 'CF7spreadsheets_update_ajax_form_data',
                CF7spreadsheets_post_id: $('#CF7spreadsheets_output_select').val(),
            }, function ($res) {
                try{
                    var $res_json = JSON.parse($res);
                    if($res_json.response == 'error'){
                        $('#CF7spreadsheets_allowed_tags').html($res_json.content);
                    }else{
                        if($res_json.response == 'success'){
                            if($.isArray($res_json.content)){
                                if($res_json.filled == false){
                                    $.each($res_json.content, function (index, value){
                                        $.each(value, function (index, value){
                                            $('#CF7spreadsheets_allowed_tags').append('<span>['+value+']</span>');
                                            $('.CF7spreadsheets_table_add').before('<div class="CF7spreadsheets_table_cell"><input type="text" name="CF7spreadsheets_output_tags[]" value="['+value+']"><button title="Remove cell" type="button" class="button CF7spreadsheets_table_remove">-</button></div>');
                                        });
                                    });
                                }else{
                                    $.each($res_json.content, function (index, value){
                                        $.each(value, function (index, value){
                                            $('#CF7spreadsheets_allowed_tags').append('<span>['+value+']</span>');
                                        });
                                    });
                                    $filled_arr = JSON.parse($res_json.filled);
                                    $.each($filled_arr, function (index, value){
                                        $('.CF7spreadsheets_table_add').before('<div class="CF7spreadsheets_table_cell"><input type="text" name="CF7spreadsheets_output_tags[]" value="'+value+'"><button title="Remove cell" type="button" class="button CF7spreadsheets_table_remove">-</button></div>');
                                    });
                                }
                            }
                        }
                    }
                }catch(e){
                    $('#CF7spreadsheets_allowed_tags').html("<p class='CF7spreadsheets_error_ajax'>"+e+"</p>");
                }
            });
        }
    }
    get_form_tags();
    $('#CF7spreadsheets_output_select').change(function () {
        get_form_tags();
    });

    //switcher
    $('.CF7spreadsheets_switcher_field').change(function (){
        var $fields = $(this).closest('.CF7spreadsheets_col_wrapper').children('.CF7spreadsheets_col_right').find('input, select');
        if($(this).prop('checked')){
            $fields.each(function (){
                $(this).removeAttr('readonly');
            });
        }else{
            $fields.each(function (){
                $(this).attr('readonly', '')
            });
        }
    });

    //table
    $('.CF7spreadsheets_table_wrapper').on('click', '.CF7spreadsheets_table_remove', function (){
        $(this).parent().remove();
    });
    $('.CF7spreadsheets_table_add').click(function (){
        $(this).before('<div class="CF7spreadsheets_table_cell"><input type="text" name="CF7spreadsheets_output_tags[]"><button title="Remove cell" type="button" class="button CF7spreadsheets_table_remove">-</button></div>');
    });
});