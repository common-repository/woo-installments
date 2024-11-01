// JavaScript Document
jQuery(document).ready(function($){

    $('.woo_inst_pay_full_btn').on('click', function(){

        $('.woo_inst_pay_installment_btn').prop('disabled', false);
        $(this).prop('disabled', true);
        $('.woo_inst_add_to_cart_wrapper').css('display', 'block');
        $('.woo_inst_tiers').css('display', 'none');
        $('.woo_inst_packages').css('display', 'none');


    });

    $('.woo_inst_pay_installment_btn').on('click', function(){

        $('.woo_inst_pay_full_btn').prop('disabled', false);
        $(this).prop('disabled', true);
        $('.woo_inst_add_to_cart_wrapper').css('display', 'none');
        $('.woo_inst_tiers').css('display', 'block');
        $('.woo_inst_packages').css('display', 'block');




    });



    function woo_inst_disable_proceed(){

        var unlinked_packages = $('.woo_inst_single_template .package_group_item').not('.unlinked').find('input:checked');

        if(unlinked_packages.length == 0){

            $('#woo_inst_proceed_with_installment').prop('disabled', true);

        }else{

            $('#woo_inst_proceed_with_installment').prop('disabled', false);
        }
    }

    woo_inst_disable_proceed();


    $('.woo_inst_single_template .package_group_item').not('.unlinked').on('click', function(){

        var current_check = $(this).find('input:checkbox');
        console.log(current_check);
        current_check.prop('checked', !current_check.prop('checked'));
        if(current_check.prop('checked')){
            $(this).addClass('package_group_item_selected');
        }else{
            $(this).removeClass('package_group_item_selected');
        }

        woo_inst_disable_proceed();
    });

    var group_items = $('.woo_inst_single_template .package_group_item');

    $.each(group_items, function(){

        var current_check = $(this).find('input:checkbox');

        if(current_check.prop('checked')){
            $(this).addClass('package_group_item_selected');
        }else{
            $(this).removeClass('package_group_item_selected');
        }

    });

    $('.woo_inst_read_more').on('click', function(e){

        e.preventDefault();

        $('.woo_inst_read_more_text').toggle();
        $('.woo_inst_read_less_text').toggle();

    });


    $('body').on('click','table .woo_inst_package_view', function(){

        $('table .woo_inst_package_tr').hide();
        $(this).parents('tr:first').next('.woo_inst_package_tr').show();

    })


    if($('.woo_inst_single_course').parents('.row:first').width() > 850){

        $('.woo_inst_single_course').removeClass('col-md-6').addClass('col-md-4');

    }

    $('.woo_inst_playlist_wrapper .woo_inst_urls ul li').on('click', function(){


        var url = $(this).data('url');

        $('.woo_inst_playlist_wrapper .woo_inst_urls ul li').removeClass('playing');
        $(this).addClass('playing');

        var current_playing = $('.woo_inst_player video source').prop('src');

        if(current_playing != url){

            $('.woo_inst_player video source').prop('src', url);
            $(".woo_inst_player video")[0].load();
            $('.woo_inst_player video')[0].play();

        }



    });


    if(woo_inst_obj.hide_full_package){


        $('.woo_inst_pay_installment_btn').click();
        $('.woo_inst_add_to_cart_wrapper').remove();
        $('.woo_inst_pay_full_btn').remove();

    }



});