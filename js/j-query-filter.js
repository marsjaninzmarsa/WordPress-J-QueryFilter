jQuery(function($) {
	var form = {html: ''};
	window.jQueryFilter = [];
	// console.log(sidebar_query_filter);
	sidebar_query_filter.forEach(function(row) {
		switch(row.type) {
			case 'list':
			case 'color_list':
				this.html += jQueryFilterList(row);
				break;
			case 'range':
				this.html += jQueryFilterRange(row);
				break;
		}
	}, form);
	$('.filters-list').html(form.html);
	$('.filters-list + input[type=submit]').hide();
	$('.filters form').deserialize( location.search.substr( 1 ) );
	window.jQueryFilter.forEach(function(row) {
		jQueryFilterGenerate(row);
	});
	$('.filters-list input[type=checkbox]:checked').parent().parent().parent().children('input[type=checkbox]').attr('checked', true);
	$('.filters-list li').each(jQueryFilterDeselectUpdate);
	$('.filters-list input[type=checkbox]').change(function() {
		// console.log('dupa');
		$(this).parent().find('ul input[type=checkbox]').attr('checked', false);
	});
	var formChange, oldForm;
	$('.filters-list :input').change(function(e) {
		// console.log(e);
		clearTimeout(formChange);
		jQueryFilterUpdateForm();
		$('.filters-list li').each(jQueryFilterDeselectUpdate);
	});
	$('.filters-list :input').keyup(function() {
		clearTimeout(formChange);
		formChange = setTimeout(function() {
			jQueryFilterUpdateForm();
		}, 500);
	});
	$('.filters-list div.deselect, .content .yours ul div.deselect, .filters .toggler div.deselect').click(jQueryFilterDeselect);
	
	jQueryFilterPaginate();
	jQueryFilterSort();

	window.onpopstate = function(event) {
		// console.log(event);
		// console.log(event.originalTarget.document.location.search.substr( 1 ));
		$('.filters form input').each(function() {
			$(this).attr('checked', false).val(this.defaultValue);
			if($(this).parent().hasClass('slider')) {
				$(this).val(null);
				slider = $(this).parent().children('.ui-slider');
				$(slider)
					.slider('values', 0, $(slider).slider('option', 'min'))
					.slider('values', 1, $(slider).slider('option', 'max'))
				$(this).parent().children('.slider-values').hide();
			}
		})
		$('.filters form').deserialize( event.originalTarget.document.location.search.substr( 1 ) );
		$('.filters-list li').each(jQueryFilterDeselectUpdate);
		if(event.state) {
			$('.content.offerListMain').html(event.state);
		} else {
			jQueryFilterUpdateForm(true);
		}
	}



	function jQueryFilterList(row) {
		var form = {html: ''};
		form.html += '<li id="'+row.name+'-filter">';
		form.html += '<span class="toggler"><span>'+row.title+'</span><div class="deselect" data-name="'+row.name+'">x</div></span>';
		form.html += '<ul class="details details-cols orientation-'+row.orientation+' '+row.type+'-type">';
		row.options.forEach(function(imput) {
			switch(row.type) {
				case 'list':
					jQueryFilterListRow(this, imput);
					break;
				case 'color_list':
					jQueryFilterColorListRow(this, imput);
					break;
			}
		}, form);
		form.html += '</ul>';
		form.html += '</li>';
		window.jQueryFilter.push(row);
		return form.html;
	}
	function jQueryFilterListRow(form, imput) {
		// console.log(imput);
		// console.log(imput);
		form.html += '<li>';
		form.html += '<input id="filtr-'+imput.taxonomy+'-'+imput.slug.replace(/ /g, '')+'" class="inc" type="checkbox" value="'+imput.slug+'" name="'+imput.taxonomy+'[]" /><label for="filtr-'+imput.taxonomy+'-'+imput.slug.replace(/ /g, '')+'">'+imput.name+'</label>';
		if(imput.children instanceof Array) {
			form.html += '<ul>';
			imput.children.forEach(function(imput) {
				jQueryFilterListRow(form, imput);
			}, form);
			form.html += '</ul>'
		}
		form.html += '</li>';
	}
	function jQueryFilterColorListRow(form, imput) {
		form.html += '<li>';
		form.html += '<input id="filtr-'+imput.name+'-'+imput.slug+'" class="inc" type="checkbox" value="'+imput.slug+'" name="'+imput.name+'[]" />';
		form.html += '<label for="filtr-'+imput.name+'-'+imput.slug+'" data-color="'+imput.description+'" title="'+imput.name+'">'+imput.name+'</label>';
		form.html += '<div data-tax="'+imput.name+'" data-slug="'+imput.slug+'" class="deselect">x</div>';
		form.html += '</li>';
	}
	function jQueryFilterRange(row) {
		row.min = row.min || 0;
		var form = '';
		var foo  = [];
		form += '<li id="'+row.name+'-filter">';
		form += '<span class="toggler"><span>'+row.title+'</span><div class="deselect" data-name="'+row.name+'">x</div></span>';
		form += '<div class="details details-cols">';
		form += '<div class="slider">';
		form += '<input id="'+row.min_name+'" class="inc" type="hidden" value="'+row.min+'" min="'+row.min+'" max="'+row.max+'" name="'+row.min_name+'" />';
		form += '<input id="'+row.max_name+'" class="inc" type="hidden" value="'+row.max+'" min="'+row.min+'" max="'+row.max+'" name="'+row.max_name+'" />';
		form += '<div id="slider-'+row.name+'-range"></div>';
		form += '<div class="slider-values">';
		form += '<div id="slider-'+row.name+'-from-text" class="slider-from-text"></div>';
		form += '<div id="slider-'+row.name+'-to-text" class="slider-to-text"></div>';
		form += '</div>';
		form += '</div>';
		form += '</div>';
		form += '</li>';
		window.jQueryFilter.push(row);
		return form;
	}

	function jQueryFilterGenerate(row) {
		if(row.type == 'range') {
			$('#slider-'+row.name+'-range').slider({
				range:  true,
				min:    row.min,
				max:    row.max,
				step:   row.step,
				values: [$('#'+row.min_name).val(), $('#'+row.max_name).val()],
				create: function(event, ui) {
					$('#slider-'+row.name+'-from-text').text($('#'+row.min_name).val()+' zł');
					$('#slider-'+row.name+'-to-text').text($('#'+row.max_name).val()+' zł'+
						($('#'+row.max_name).val() >= row.max ? ' +' : '')
					);
					$('#'+row.min_name).val(null);
					$('#'+row.max_name).val(null);
				},
				slide:  function(event, ui) {
					$('#'+row.min_name).val(
						(ui.values[0] != row.min) ? ui.values[0] : null
					);
					$('#'+row.max_name).val(
						(ui.values[1] != row.max) ? ui.values[1] : null
					);
					$('#slider-'+row.name+'-from-text').text(ui.values[0]+' zł');
					$('#slider-'+row.name+'-to-text').text(ui.values[1]+' zł'+
						(ui.values[1] >= row.max ? ' +' : '')
					);
					$('#slider-'+row.name+'-range').parent().children('.slider-values').show();
				},
				stop: function(event, ui) {
					jQueryFilterUpdateForm();
				}
			});
		} if(row.type == 'color_list') {
			$('#'+row.name+'-filter li label').each(function() {
				color = $(this).attr('data-color');
				if(color.search('/') > 0) {
					color = color.split('/');
					$(this).css('border-top', '20px solid '+color[0]);
					$(this).css('border-right', '20px solid '+color[1]);
					$(this).css('width', 0);
					$(this).css('height', 0);
					$(this).css('padding', 0);
				} else if(color.search('\n') > 0) {
					color = color.split('\n');
					$(this).css('background', 'linear-gradient(to bottom, '+color[0]+' 0%, '+color[1]+' 100%)');
				} else if(color.length > 0) {
					$(this).css('background-color', color);
					color = [
						jQuery.Color(color).lightness(jQuery.Color(color).lightness()*1.2).toHexString(),
						jQuery.Color(color).lightness(jQuery.Color(color).lightness()*0.9).toHexString()
					];
					$(this).css('background', 'linear-gradient(to bottom, '+color[0]+' 0%, '+color[1]+' 100%)');
				}
			});
		}
	}

	function jQueryFilterUpdateForm(nohistory) {
		newForm = $('.filters form').serialize().replace(/[^&]+=\.?(?:&|$)/g, '').replace(/&$/, '');
		// console.log(newForm);
		// console.log(window.oldForm);
		if (newForm == window.oldForm) {
			return;
		} else {
			newPage = parseInt($('.filters form input[name=paged]').val());
			if (newPage == window.oldPage) {
				page = window.oldPage = $('.filters form input[name=paged]').val(1);
				newForm = $('.filters form').serialize().replace(/[^&]+=\.?(?:&|$)/g, '').replace(/&$/, '');
			} else {
				page = window.oldPage = newPage;
			}
			form = window.oldForm = newForm;
			form = form.replace(/\&paged=[\d]/g, '').replace(/\paged=[\d]/g, '').replace(/&$/, '');
		}
		// console.log(form);
		key  = window.sidebar_query_filter_ajax.search_url;
		if(parseInt($('.filters form input[name=paged]').val()) > 1) {
			key += 'page/';
			key += $('.filters form input[name=paged]').val();
		}
		key += (form) ? '?s=&search=1&' : '';
		// console.log(key);
		// console.log(page);
		// console.log(sidebar_query_filter_ajax.ajaxurl);
		// console.log($('.filters form input[name=paged]').val());
		$('.filters form').ajaxSubmit({
			url: sidebar_query_filter_ajax.ajaxurl,
			data: {
				action : 'sidebar_query_filter',
				search : (form) ? '1' : '',
				// paged  : page,
				referer: window.location.origin+window.location.pathname
			},
			target: '.content.offerListMain',
			beforeSubmit: function() {
				// console.log('doing-ajax');
				// if(!$('.content.offerListMain > .product-grid, .content.offerListMain > .product-list').length) {
					// window.location.href = key + form.replace(/\&paged=[\d]/g, '');
				// 	return false;
				// }
				$('.content.offerListMain ul.product-grid, .content.offerListMain ul.product-list').addClass('loading');
			},
			success: function(responseText) {
				// console.log('end-ajax');
				$('.content.offerListMain, .button.offerLoadMoar').removeClass('loading');
				$('.button.offerLoadMoar').show();
				if($('.noResults').length || $('.content.offerListMain .eot').length) {
					$('.button.offerLoadMoar').hide();
				}
				if(!nohistory) {
					window.history.pushState(responseText, 'Work4Tech', key + form.replace(/\&paged=[\d]/g, ''));
				}
				jQueryFilterPaginate();
				jQueryFilterSort();
				if(form) {
					$('.span9 .promo').hide();
				} else {
					$('.span9 .promo').show();
				}
			}       
		});
	}

	function jQueryFilterDeselect() {
		$('.filters-list #'+$(this).attr('data-name')+'-filter input').each(function() {
			$(this).attr('checked', false).val(this.defaultValue).change();
			if($(this).parent().hasClass('slider')) {
				$(this).val(null);
				slider = $(this).parent().children('.ui-slider');
				$(slider)
					.slider('values', 0, $(slider).slider('option', 'min'))
					.slider('values', 1, $(slider).slider('option', 'max'))
					.trigger('slide')
				.trigger('stop');
				$(this).parent().children('.slider-values').hide();
			}
		});
		$('.filters-list #filtr-'+$(this).attr('data-tax')+'-'+$(this).attr('data-slug')).attr('checked', false).change();
		$(this).removeClass('active');
	};
	function jQueryFilterDeselectUpdate() {
		$(this).find('.toggler .deselect').removeClass('active');
		row = this;
		$(this).find(':input').each(function(i, e, row) {
			if($(e).attr('checked')) {
				// console.log($(this).parents('.details').parents('li'));
				$(this).parents('.details').parents('li').find('.toggler .deselect').addClass('active');
			}
		});
	}

	function jQueryFilterPaginate() {
		$('.wp-pagenavi a.page').click(function(event) {
			event.preventDefault();
			// console.log($('.filters form input[name=paged]').val($(this).text()));
			jQueryFilterUpdateForm();
			$('html, body').animate({
				scrollTop: $(".content.offerListMain").offset().top
			}, 500);
		});
		$('.wp-pagenavi a.nextpostslink').click(function(event) {
			event.preventDefault();
			// console.log($('.filters form input[name=paged]').val(parseInt($('.filters form input[name=paged]').val()) + 1));
			jQueryFilterUpdateForm();
				$('html, body').animate({
				scrollTop: $(".content.offerListMain").offset().top
			}, 500);

		});
		$('.wp-pagenavi a.previouspostslink').click(function(event) {
			event.preventDefault();
			// console.log($('.filters form input[name=paged]').val(parseInt($('.filters form input[name=paged]').val()) - 1));
			jQueryFilterUpdateForm();
				$('html, body').animate({
				scrollTop: $(".content.offerListMain").offset().top
			}, 500);

		});
	}

	function jQueryFilterSort() {
		$('#sort select[name=sortby]').change(function() {
			clearTimeout(formChange);
			$('.filters form input[name=sortby]').val($(this).val());
			$('.filters form input[name=sort]').val(
				($(this).val()) ? 'price' : ''
			);
			jQueryFilterUpdateForm();
		});
	}

	function jQueryFilterLoadMoar() {
		if(!($('.button.offerLoadMoar').hasClass('loading') || $('.noResults').length)) {
			$('.button.offerLoadMoar').show().addClass('loading');
			$('.filters form [name=paged]').remove();
			$('.filters form').ajaxSubmit({
				url: sidebar_query_filter_ajax.ajaxurl,
				data: {
					action : 'sidebar_query_filter',
					search : (form) ? '1' : '',
					offset : $('.offerListMain.content div[data-lp]').last().attr('data-lp'),
					referer: window.location.origin+window.location.pathname
				},
				beforeSubmit: function() {
					
				},
				success: function(responseText) {
					console.log(responseText);
					if(responseText) {
						$('.offerListMain.content').append(responseText);
						$('.button.offerLoadMoar').removeClass('loading');
					} else {
						$('.button.offerLoadMoar').hide();
					}
					if($('.noResults').length || $('.content .eot').length) {
						$('.button.offerLoadMoar').hide();
					}
				}
			});
		}
	}

	$(window).bind('scroll', function() {
		if($(window).scrollTop()+500 >= $('ul.offerListMain.content').offset().top + $('ul.offerListMain.content').outerHeight() - window.innerHeight) {
			jQueryFilterLoadMoar();
		}
	});

	$('.button.offerLoadMoar').click(function() {
		jQueryFilterLoadMoar();
	});

	$(jQueryFilterLoadMoar);

	if($('.noResults').length) {
		$('.button.offerLoadMoar').hide();
	}

	$.fn.scrollTo = function( target, options, callback ){
		if(typeof options == 'function' && arguments.length == 2){ callback = options; options = target; }
		var settings = $.extend({
			scrollTarget  : target,
			offsetTop     : 50,
			duration      : 500,
			easing        : 'swing'
		}, options);
		return this.each(function(){
			var scrollPane = $(this);
			var scrollTarget = (typeof settings.scrollTarget == "number") ? settings.scrollTarget : $(settings.scrollTarget);
			var scrollY = (typeof scrollTarget == "number") ? scrollTarget : scrollTarget.offset().top + scrollPane.scrollTop() - parseInt(settings.offsetTop);
			scrollPane.animate({scrollTop : scrollY }, parseInt(settings.duration), settings.easing, function(){
				if (typeof callback == 'function') { callback.call(this); }
			});
		});
	}

});