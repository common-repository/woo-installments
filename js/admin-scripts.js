// JavaScript Document
function woo_inst_parse_query_string(query) {
	
	var vars = query.split("&");
	
	var query_string = {};
	
	for (var i = 0; i < vars.length; i++) {
	
	var pair = vars[i].split("=");
	
	// If first entry with this name
	
	if (typeof query_string[pair[0]] === "undefined") {
	
	  query_string[pair[0]] = decodeURIComponent(pair[1]);
	
	  // If second entry with this name
	
	} else if (typeof query_string[pair[0]] === "string") {
	
	  var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
	
	  query_string[pair[0]] = arr;
	
	  // If third or later entry with this name
	
	} else {
	
	  query_string[pair[0]].push(decodeURIComponent(pair[1]));
	
	}
	
	}
	
	return query_string;

}

function woo_inst_get_total_price(){

	var price_input = $('body').find('#woo_inst_settings_tab_area .woo_inst_add_row .woo_inst_package_price')

	console.log(price_input);
	var total_price = 0;

	$.each(price_input, function(){

			console.log($(this).val());
			console.log($(this).val().length);

			if($(this).val().length != 0){

				total_price += $(this).val();
			}

	});

	alert(total_price);
	return total_price;


}

	

jQuery(document).ready(function($){

		$('.premium-col').on('click', function(){
			$('.premium-tab').click();
		});

		$('.woo_inst_checkout_options').on('click', function(){
			if($(this).is(':checked')){
				$(this).parent().addClass('selected');
			}else{
				$(this).parent().removeClass('selected');
			}
		});


        function set_active_page_url(){

            var active_page = $('.woo_inst_pagination .page-item.active');
            var current_url = new URL(window.location.href);
            if(active_page.data('page') == 1){

                current_url.searchParams.delete('woo_inst_current_page');

            }else{

                current_url.searchParams.set('woo_inst_current_page', active_page.data('page'));

            }
            window.history.replaceState('', '', current_url.href);

        }



		
		$('.wc_inst_settings_div a.nav-tab').click(function(){
			$(this).siblings().removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.nav-tab-content').hide();
			$('.nav-tab-content').eq($(this).index()).show();
			window.history.replaceState('', '', woo_inst_obj.this_url+'&t='+$(this).index());
			
			$('form input[name="woo_inst_tn"]').val($(this).index());

			woo_inst_obj.woo_inst_tab = $(this).index();

			set_active_page_url();

		});
				
		var woo_inst_query = window.location.search.substring(1);
		
		var woo_inst_qs = woo_inst_parse_query_string(woo_inst_query);		
		
		if(typeof(woo_inst_qs.t)!='undefined'){
			$('.wc_inst_settings_div a.nav-tab').eq(woo_inst_qs.t).click();
		}

		var pkg_select = $('#woo_inst_settings_tab_area .woo_inst_input_row select');

		$('body').on('click','#woo_inst_settings_tab_area .woo_inst_add_row', function(e){

			e.preventDefault();


			var panel_wrapper = $('#woo_inst_settings_tab_area');
			var input_row = $('body #woo_inst_settings_tab_area .woo_inst_input_row');
			var parent_row = $(this).parents('.woo_inst_input_row');

			var error = [];
			var total_price = 0;
			var product_price = panel_wrapper.data('product_price');




			var parent_row = $(this).parents('.woo_inst_input_row');
			parent_row.removeClass('woo_inst_row_bg');
			var parent_clone = parent_row.clone();

			parent_clone.find('input').val("");
			parent_clone.find('textarea').val("");
			parent_clone.find('input').prop('disabled', true);
			parent_clone.find('select').hide().val([]);
			parent_clone.find('.woo_inst_image_placeholder').show();
			parent_clone.find('.woo_inst_package_img').hide();
			parent_clone.find('.woo_inst_package_img img').prop('src', '');





			parent_row.after(parent_clone);

			var parent_next = parent_row.next();
			var product_id = panel_wrapper.data('product');
			$('body').find('.woo_inst_selection_placeholder').show();


			var data = {

				action : 'woo_inst_save_package_meta',
				woo_inst_save_package_meta : true,
				woo_inst_package_id: parent_row.data('package'),
				woo_inst_product_id: panel_wrapper.data('product'),

			};


			$.post(ajaxurl, data, function(response){

				response = parseInt(response);

				var price_input = parent_next.find('input.woo_inst_package_price');
				var title_input = parent_next.find('input.woo_inst_package_title');
				var description_input = parent_next.find('textarea.woo_inst_package_description');
				var post_lock_selection = parent_next.find('select.woo_inst_post_lock');
				// console.log(post_lock_selection);

				parent_next.data('package', response);


				price_input.attr('name', 'woo_inst_package_data['+response+'][price]');
				title_input.attr('name', 'woo_inst_package_data['+response+'][title]');
				description_input.attr('name', 'woo_inst_package_data['+response+'][e_description]');
				post_lock_selection.attr('name', 'woo_inst_post_lock['+product_id+'|'+response+'][]');

				setTimeout(function () {
					price_input.click();
				});


				parent_next.find('input').prop('disabled', false);



			});

			$(this).hide();


		});


		$('body').on('click', '#woo_inst_settings_tab_area .woo_inst_del_row', function(e){

		e.preventDefault();

		pkg_select.slideUp();


		var panel_wrapper = $('#woo_inst_settings_tab_area');
		var input_row = $('body #woo_inst_settings_tab_area .woo_inst_input_row');
		var last_row_index = $('body #woo_inst_settings_tab_area .woo_inst_input_row:last').index();
		var parent_row = $(this).parents('.woo_inst_input_row');
		var parent_index = parent_row.index();



			var del = confirm(woo_inst_obj.del_confirm);

		if(del == true){

			$('body').find('.woo_inst_selection_placeholder').show();

			var data = {

				action : 'woo_inst_del_package_meta',
				woo_inst_del_package_meta : true,
				woo_inst_package_id: parent_row.data('package'),
				woo_inst_product_id: panel_wrapper.data('product'),

			};

			// console.log(data);


			$.post(ajaxurl, data, function(response){

				response = JSON.parse(response);
				// console.log(response);
			});


			if(last_row_index == parent_index){

				// parent_row.prev().find('.woo_inst_add_row').show();

			}

			parent_row.remove();

			if(input_row.length > 1){


			}else{

				$('#woo_inst_settings_tab_area .add_package').css('display', 'inline');
				$('#woo_inst_settings_tab_area .add_package').parents('.row').css('display', 'flex');
				$('.woo_inst_example_picture').show();
				$('.woo_inst_save_row').css('display', 'none');
				// parent_row.find('input').val("");
			}

		}



	});

		$('body').on('click', '#woo_inst_settings_tab_area .add_package', function(e) {

				e.preventDefault();

				pkg_select.slideUp();
				$('.woo_inst_save_row').css('display', 'flex');


				var package_row = $('#woo_inst_settings_tab_area .woo_inst_clone_row');
				var input_row = $('.woo_inst_input_row');
				$('.woo_inst_example_picture').hide();

				if (input_row.length == 0) {


					var clone_row = package_row.clone();

					var add_package_btn = $(this);
					// add_package_btn.css('display', 'none');

					clone_row.removeClass('woo_inst_clone_row');
					clone_row.addClass('woo_inst_input_row');
					clone_row.css('display', 'flex');

					package_row.after(clone_row);


					var panel_wrapper = $('#woo_inst_settings_tab_area');
					var product_id = panel_wrapper.data('product');

					var data = {

						action: 'woo_inst_save_package_meta',
						woo_inst_save_package_meta: true,
						woo_inst_product_id: product_id,

					};


					$.post(ajaxurl, data, function (response){

						response = parseInt(response);
						// console.log(response);


						var price_input = clone_row.find('input.woo_inst_package_price');
						var title_input = clone_row.find('input.woo_inst_package_title');
						var description_input = clone_row.find('textarea.woo_inst_package_description');
						var post_lock_selection = clone_row.find('select.woo_inst_post_lock');
						clone_row.data('package', response);
						// console.log(clone_row);

						price_input.attr('name', 'woo_inst_package_data[' + response + '][price]');
						title_input.attr('name', 'woo_inst_package_data[' + response + '][title]');
						description_input.attr('name', 'woo_inst_package_data[' + response + '][e_description]');
						post_lock_selection.attr('name', 'woo_inst_post_lock[' + product_id + '|' + response + '][]');

						setTimeout(function () {
							price_input.click();
						});


					});

				}else{

					var input_row_last = $('body').find('.woo_inst_input_row').last();
					input_row_last.find('.woo_inst_add_row').click();





				}


		});

		$('body').on('click', '.woo_inst_selection_placeholder', function(){

			var this_select = $(this).next('select');
			var other_placeholder = $('.woo_inst_selection_placeholder').not(this);
			var other_select = $('#woo_inst_settings_tab_area .woo_inst_input_row select').not(this_select);


			other_select.hide();
			this_select.toggle();
			$(this).hide();
			other_placeholder.show();

		});

		$('body').on('click', '.woo_inst_selection_pkg_placeholder', function(){

			var this_select = $(this).next('select');
			var other_placeholder = $('.woo_inst_selection_pkg_placeholder').not(this);
			var other_select = $('.woo_inst_ptype_group  select').not(this_select);
			var parent_item = $(this).parents('.woo_inst_ptype_group_item');
			var other_item = $('.woo_inst_ptype_group_item').not(parent_item);


			parent_item.addClass('woo_inst_row_bg');
			other_item.removeClass('woo_inst_row_bg');



			//
			// other_select.slideUp();
			// this_select.slideToggle();

			if(this_select.length > 0){

				other_placeholder.show();
				$(this).hide();
				other_select.hide();
				this_select.toggle();



			}else{

				alert(woo_inst_obj.no_packages_defined);

			}

		});

		$('.woo_inst_expand_collapse').on('click', function(e){

			e.preventDefault();

			$(this).find('.woo_inst_collapse').toggle();
			$(this).find('.woo_inst_expand').toggle();
			$(this).next('ul').slideToggle();

		});

		$('body').on('click', '.woo_inst_input_row', function () {


			$('.woo_inst_input_row').removeClass('woo_inst_row_bg');
			$(this).addClass('woo_inst_row_bg');

		});


		$('.woo_inst_template_card').on('click', function(){

			$('.woo_inst_template_card').removeClass('woo_inst_selected');
			$(this).addClass('woo_inst_selected');
			var template = $(this).data('template');

			$('.woo_inst_templates_wrapper [name="woo_inst_selected_template"]').val(template);

		});

		var selected_template = $('.woo_inst_templates_wrapper [name="woo_inst_selected_template"]').val();
		$('.woo_inst_template_card[data-template="'+selected_template+'"]').addClass('woo_inst_selected');

		$('body').on('click', '.woo_inst_package_img .woo_inst_remove_img', function () {


			var placeholder_parent = $(this).parents().eq(1);
			var current_placeholder = placeholder_parent.find('.woo_inst_image_placeholder');
			var current_image = placeholder_parent.find('.woo_inst_package_img');
			var pkg_id = current_placeholder.parents('.woo_inst_input_row').data('package');

			placeholder_parent.find('input').val('');
			current_image.find('img').prop('src', '');
			current_placeholder.show();
			current_image.hide();


		});

		if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
			$('body').on('click','.woo_inst_image_placeholder, .woo_inst_package_img :not(.woo_inst_remove_img)', function (e) {

				e.preventDefault();

				if ($(this).not('.woo_inst_remove_img')) {

				var placeholder_parent = $(this).parents().eq(0);
				var current_placeholder = placeholder_parent.find('.woo_inst_image_placeholder');
				var current_image = placeholder_parent.find('.woo_inst_package_img');
				var pkg_id = current_placeholder.parents('.woo_inst_input_row').data('package');

				// var id = $(this).find('.hi_vals');
				wp.media.editor.send.attachment = function (props, attachment) {
					// id.val(attachment.id);
					placeholder_parent.find('img').prop('src', attachment.url);
					placeholder_parent.find('input:hidden').val(attachment.id);
					placeholder_parent.find('input:hidden').prop('name', 'woo_inst_package_data[' + pkg_id + '][image]');
					current_placeholder.hide();
					current_image.show();


				};
				// console.log(images_ids);
				wp.media.editor.open($(this));

				return false;

			}
			});

		}


        var woo_inst_load_modal = $('.woo_inst_load_modal');
        var woo_inst_success = $('.woo_inst_playlist_alerts .alert-success');
        var woo_inst_warning = $('.woo_inst_playlist_alerts .alert-warning');
        var playlist_update_btn =  $('.woo_inst_playlist_save.update');
        var playlist_save_btn =  $('.woo_inst_playlist_save.save');
        var playlist_create = $('.woo_inst_playlist_create_new');
        var playlist_show_all = $('.woo_inst_playlist_show_all');
        var table_row = $('.woo_inst_playlist_table_row');
        var playlist_form = $('#woo_inst_playlist_form')



		function woo_inst_reset_form(){

            $('form#woo_inst_playlist_form').trigger('reset');
            $('.woo_inst_playlist_data .row.playlist_url.clone').remove();

             $('input[name="woo_inst_playlist_index"]').val('-1');

            playlist_update_btn.hide();
            playlist_save_btn.show();

            playlist_form.show();
            table_row.hide();

		}


		$('.woo_inst_add_new_url').on('click', function(e){

		    e.preventDefault();

            var url_row = $('.woo_inst_playlist_data .row.playlist_url:first').clone();
            url_row.addClass('clone');
            url_row.find('input:text').val('');


            $('.woo_inst_playlist_data').append(url_row);

		})


		$('body').on('click', '.woo_inst_playlist_data .remove_url', function(e){

		    e.preventDefault();


            if($('.woo_inst_playlist_data .row.playlist_url').length > 1){

                $(this).parents('.row:first').remove();

            }else{

                $('.woo_inst_playlist_data .row.playlist_url input:text').val('');

            }

            $('.woo_inst_playlist_data .row.playlist_url:first').removeClass('clone');


		});





        var form_submit_option = {

            'url' : ajaxurl,

            beforeSubmit : function(form_data){

                woo_inst_success.hide();
                woo_inst_warning.hide();

                var required_for_short_code = ['show_feed_title', 'keep_feed_link'];

                $('.rfw_shortcode_row').hide();

                var error = [];
                var url_count = 0;
                var url_empty = 0;



                $.each(form_data, function(f_key, f_value){

                    if(f_value.value == "" && f_value.required == true){

                        error.push(true);

                    }

                    if(f_value.name == "woo_inst_playlist[urls][]"){


                            url_count++;

                        if(f_value.value == ""){

                            url_empty++;
                        }

                    }

                });

                if(url_count == url_empty){

                    alert(woo_inst_obj.url_alert);

                    return false;

                }



                if($.inArray(true, error) != -1){

                    alert(woo_inst_obj.empty_alert);

                    return false;

                }






                woo_inst_load_modal.show();



            },
            success : function(response, code){

                        woo_inst_load_modal.hide();


    //            $('.rfw_shortcode_li').html(response.shortcode);
    //            $('.rfw_shortcode_row').show();

                if(response.status){

                    if(response.status != 'exist'){

                        woo_inst_success.html(response.alert_text);
                        woo_inst_success.show();

                        table_row.find('table tbody').html(response.playlist_body);

                        playlist_show_all.click();


                    }else{

                        woo_inst_warning.html(response.alert_text);
                        woo_inst_warning.show();



                    }

                    setTimeout(function(){

                        woo_inst_success.hide();
                        woo_inst_warning.hide();

                    }, 5000);






                }




            },

            error : function(e){

               woo_inst_load_modal.hide();


            },




        };

        $('.woo_inst_playlist_save').on('click', function(e){

            e.preventDefault();


            $('form#woo_inst_playlist_form').ajaxSubmit(form_submit_option);

        });


        $('body').on('click', '.woo_inst_playlist_table .dashicons.view', function(e){

            e.preventDefault();


            $('.woo_inst_playlist_table .urls_row').hide();

            $(this).parents('tr:first').next('.urls_row').show('slow');

        });


        $('body').on('click', '.woo_inst_playlist_table .dashicons.delete', function(e){

            e.preventDefault();


            var confirm_del = confirm(woo_inst_obj.del_confirm_playlist);

            if(!confirm){

                return;

            }

            var id = $(this).data('id');

            var data = {

                action : 'woo_inst_delete_playlist',
                nonce : woo_inst_obj.woo_inst_nonce,
                woo_inst_playlist_id : id,

            }

            console.log(data);

            woo_inst_load_modal.show();

            $.post(ajaxurl, data, function(response, code){

                woo_inst_load_modal.hide();

                if(code == 'success'){

                     if(response.status){


                        woo_inst_success.html(response.alert_text);
                        woo_inst_success.show();

                        table_row.find('table tbody').html(response.playlist_body);

                        playlist_show_all.click();



                     }

                }


            });

        });



        playlist_create.on('click', function(){

            woo_inst_reset_form();

            playlist_create.hide();
            playlist_show_all.show();

        })

        playlist_show_all.on('click', function(){

            playlist_create.click();


            playlist_create.show();
            playlist_show_all.hide();

            playlist_form.hide();
            table_row.show();

        })



        $('body').on('click', '.woo_inst_playlist_table .dashicons.edit', function(e){

            e.preventDefault();

            playlist_create.click();


            var id = $(this).data('id');
            var parent_row = $(this).parents('tr:first');
            var title = parent_row.data('title');
            var urls_row = parent_row.next('tr.urls_row:first');
            var urls_table_row = urls_row.find('table tbody tr');


            $('#woo_inst_playlist_title').val(title);

            $('input[name="woo_inst_playlist_index"]').val(id);

            playlist_update_btn.show();
            playlist_save_btn.hide();

            var counter = 0;

            $.each(urls_table_row, function(){

                var url_td = $(this).find('td.url_td');
                var url = url_td.find('a').prop('href');

                if(counter > 0){

                    $('.woo_inst_add_new_url').click();


                }


                $('.woo_inst_playlist_data .playlist_url:last input:text').val(url);

                counter++;



            });


        });

        //pagination


        $('body').on('click','.woo_inst_pagination .page-item.active', function(e){



            e.preventDefault();



        });




        $('body').on('click','.woo_inst_pagination .page-item:not(.disabled):not(.active)', function(e){

        e.preventDefault();

        var max_page = $('.woo_inst_pagination').data('maxpage');
        var active_page = $('.woo_inst_pagination .page-item.active');
        var page = $(this).data('page');
        var all_page_items = $('.woo_inst_pagination .page-item');
        var active_page_val = active_page.data('page');

        switch (page) {

            case 'next':

                page = active_page_val+1;

                break;
            case 'previous':

                page = active_page_val-1;

                break;

            default:

                break;

        }


        var new_active_page = $('.woo_inst_pagination .page-item[data-page="'+page+'"]');
        var next = $('.woo_inst_pagination .page-item[data-page="next"]');
        var previous = $('.woo_inst_pagination .page-item[data-page="previous"]');



        new_active_page.find('.text').hide();
        new_active_page.find('.spinner-border').show();




        var data = {
            action : 'woo_inst_get_paginated_table',
            nonce : woo_inst_obj.woo_inst_nonce,
            load_page : page,
        }

        $.post(ajaxurl, data, function(response, code){

            if(code == 'success'){

                $('input.woo_inst_current_page').val(data.load_page);


                $('table tbody#woo_inst_products_body').html(response);

                new_active_page.find('.text').show();
                new_active_page.find('.spinner-border').hide();

                all_page_items.removeClass('active');
                all_page_items.removeClass('disabled');
                new_active_page.addClass('active');

                if(max_page == page){
                    next.addClass('disabled');
                }

                if(1 == page){
                    previous.addClass('disabled');
                }

       			set_active_page_url();


            }

        });







        // alert(page);




    });


        $('body').on('change', 'select#woo_inst_options_items_per_page', function(e){

            e.preventDefault();

            var data = {
                action : 'woo_inst_save_items_per_page',
                nonce : woo_inst_obj.woo_inst_nonce,
                woo_inst_items_per_page : $(this).val(),
            }


            $.post(ajaxurl, data, function(response, code){

                console.log(response);

                if(code == 'success' && response.status){

                    var current_url = new URL(window.location.href);
                    current_url.searchParams.delete('woo_inst_current_page');
                    window.history.replaceState('', '', current_url.href);
                    window.location.href = window.location.href;

                }

            });


        });


        $('body').on('change', '#woo_inst_products_body [name="woo_inst_settings[woo_inst_products][]"]', function(){


                                    var this_row = $(this).parents('tr:first');
                                    console.log(this_row);
                                    var this_input = $(this);
                                    var package_checkbox = this_row.find('[name="woo_inst_pro_settings[package][]"]');
                                    var full_checkbox = this_row.find('[name="woo_inst_pro_settings[full][]"]');

                                    console.log(package_checkbox);

                                    if($(this).prop('checked')){

                                        package_checkbox.prop('disabled', false);
                                        full_checkbox.prop('disabled', false);
                                        package_checkbox.prop('checked', true);

                                    }else{

                                        package_checkbox.prop('checked', false);
                                        package_checkbox.prop('disabled', true);
                                        full_checkbox.prop('checked', false);
                                        full_checkbox.prop('disabled', true);

                                    }


        });

        var demo_alert = $('.woo_inst_demo_alert')


        $('body').on('click', '#woo_inst_add_demo_content', function(e){

            e.preventDefault();

            var spinner = $(this).find('.w_spinner');
            spinner.removeClass('d-none');

            var data = {

                action : 'woo_inst_add_demo_content',
                nonce : woo_inst_obj.woo_inst_nonce,
                woo_inst_add_content : true,
            }


            $.post(ajaxurl, data, function(response){

                spinner.addClass('d-none');

                if(response.status){


                   window.location.href = window.location.href;


                }

            });

        });

        $('body').on('click', '#woo_inst_remove_demo_content', function(e){

                    e.preventDefault();

                    var spinner = $(this).find('.w_spinner');
                    spinner.removeClass('d-none');

                    var data = {

                        action : 'woo_inst_remove_demo_content',
                        nonce : woo_inst_obj.woo_inst_nonce,
                        woo_inst_remove_content : true,
                    }


                    $.post(ajaxurl, data, function(response){

                        spinner.addClass('d-none');

                        if(response.status){

                           window.location.href = window.location.href;

                        }

                    });

        });


});		