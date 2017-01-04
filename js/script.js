jQuery(document).ready(function ($) {
    var file_string = '';
    var file_name = '';

    var readURLE = function (input) {
        $('.CF7spreadsheets_status').html('<span class="CF7spreadsheets_loading"></span>');
        var reader = new FileReader();
        reader.fileNameSet = input.files[0].name;
        reader.onload = function (e, test) {
            $.getJSON(e.target.result).complete(function (json){
                $('.CF7spreadsheets_status').html('');
                file_string = JSON.stringify(json.responseJSON);
            });
            file_name = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    };
    $('#CF7spreadsheets_option_file').change(function(){
        readURLE(this);
    });

    $('#CF7spreadsheets_option_submit').click(function () {
        $('.CF7spreadsheets_response').html('');
        $.post(ajaxurl, {
            action: 'CF7spreadsheets_update_ajax',
            processData: false,
            contentType: false,
            CF7spreadsheets_option_url: $('#CF7spreadsheets_option_url').val(),
            CF7spreadsheets_option_id: $('#CF7spreadsheets_option_id').val(),
            CF7spreadsheets_option_mail: $('#CF7spreadsheets_option_mail').prop('checked'),
            CF7spreadsheets_option_time: $('#CF7spreadsheets_option_time').prop('checked'),
            CF7spreadsheets_option_file: file_string,
            CF7spreadsheets_option_file_name: file_name,
        }, function ($res) {
            $('.CF7spreadsheets_response').html($res);
        });
    });
});