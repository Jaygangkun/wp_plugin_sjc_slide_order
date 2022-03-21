(function($){
    $(document).ready(function(){
        $('#sjc_slide_order_table').DataTable({
            'ordering': false
        });

        $(document).on('click', '#btn_table_keep', function(){
            $.ajax({
                url: ajax_url,
                type: 'post',
                data: {
                    action: 'sjc_table_keep'
                },
                dataType: 'json',
                success: function(resp) {
                    if(!resp.success) {
                        alert(resp.message);
                    }
                    else {
                        $('#alert_message').slideUp();
                        
                    }
                }
            })
        })

        $(document).on('click', '#btn_table_overwrite', function(){

            $.ajax({
                url: ajax_url,
                type: 'post',
                data: {
                    action: 'sjc_table_overwrite'
                },
                dataType: 'json',
                success: function(resp) {
                    if(!resp.success) {
                        alert(resp.message);
                    }
                    else {
                        // $('#alert_message').slideUp();
                        location.reload();
                    }
                }
            })
        })

        $(document).on('click', '.btn-order-change', function() {
            var order_input_wrap = $(this).parents('.order-input-wrap');
            var order_input = $(order_input_wrap).find('input');

            $('.order-input-wrap input').removeClass('border-danger');

            if($(order_input).val() == '') {
                $(order_input).addClass('border-danger');
                return;
            }

            $.ajax({
                url: ajax_url,
                type: 'post',
                data: {
                    action: 'sjc_order_change',
                    post_id: $(order_input_wrap).attr('id'),
                    slide_order: $(order_input).val()
                },
                dataType: 'json',
                success: function(resp) {
                    if(!resp.success) {
                        alert(resp.message);
                    }
                    else {
                        alert('Success Added');
                        location.reload();
                    }
                }
            })
        })
    })
})(jQuery)