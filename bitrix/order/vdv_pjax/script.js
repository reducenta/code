$(document).on('submit', 'form[data-pjax]', function(event) {
    $.pjax.submit(event, '#vdv_order', {
        push: false,
        fragment: '.order_form',
        scrollTo:false,
        type:'POST',
    })
})


$(document).on('pjax:complete', function() {
    $('#vdv_order').removeClass('stubbed');
    $('#property_PHONE').inputmask({"mask": "+7 (999) 999-99-99"});
});

$(document).on('pjax:send', function() {
    $('#vdv_order').addClass('stubbed');
})

$(document).ready(function () {

    $('#property_PHONE').inputmask({"mask": "+7 (999) 999-99-99"});

    function refresh_form()
    {
        $('form[data-pjax]').submit();
    }

    function send_location_request(q){
        var query = {
            c: 'opensource:order',
            action: 'searchLocation',
            mode: 'ajax',
            q: q
        };

        $.ajax({
            url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
            type: 'POST',
            success: function (res) {
                $('.locations_list').empty().show();
                if(res.data.length > 0){
                    $.each(res.data, function(i, item){
                        $('<div class="item" data-id="'+item.code+'"><div class="location_name">'+item.name+'</div><div class="location_region">'+item.label+'</div></div>').appendTo($('.locations_list'));
                    })
                }else{
                    $('<div class="item"><div class="location_name">Местоположение не найдено</div></div>').appendTo($('.locations_list'));
                }
            }
        });
    }

    $(document).on('click', '.locations_list .item', function(){
        $(this).parent().hide();
        $('input#property_LOCATION').val($(this).data('id'));
        $('input#location_search').val($(this).find('.location_name').text());
        refresh_form();
    })

    $(document).on('keyup', 'input#location_search', function(e){

        var $this = $(this);
        var $delay = 200;

        clearTimeout($this.data('timer'));

        $this.data('timer', setTimeout(function(){
            $this.removeData('timer');
            send_location_request($this.val());
        }, $delay));


    });


    $(document).on('click', '.person_types .vdv_order_radio', function(){
        let type = $(this).find('input[type=radio]').val();

        $(this).find('input[type=radio]').prop('checked', true);
        $(this).closest('.radio_group').find('.vdv_order_radio').not($(this)).removeClass('checked');
        $(this).addClass('checked');

        refresh_form();
    })


    $(document).on('click','.options_list.delivery .item', function(){
        let name = $(this).text();
        let id = $(this).data('id');

        $(this).parent().prev().find('span').eq(0).text(name);
        $(this).closest('.options_list').find('input[value='+id+']').prop('checked', true);

        refresh_form();
    })

    $(document).on('click', '.options_list.pay_systems .item', function(){

        let name = $(this).text();
        let id = $(this).data('id');

        $(this).parent().prev().find('span').eq(0).text(name);
        $(this).closest('.options_list').find('input[value='+id+']').prop('checked', true);

        refresh_form();
    })

    $(document).on('click', '.order_button', function(){

        $('input[name=save]').val('y');
        refresh_form();
    })
});
