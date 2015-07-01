jQuery(function($) {

	$('.filters-list .toggler').append('<div class="deselect">x</div>');
	$('.filters-list input[type=checkbox]:checked').parent().parent().parent().children('input[type=checkbox]').attr('checked', true);
	$('.filters-list > li').each(jQueryFilterDeselectUpdate);
	$('.filters-list input[type=checkbox]').change(function() {
		$(this).parent().find('ul input[type=checkbox]').attr('checked', false);
		$(this).parents('.details').parent().each(jQueryFilterDeselectUpdate);
	});
	$('.filters-list div.deselect').click(jQueryFilterDeselect);

	function jQueryFilterDeselect() {
		$(this).parent().parent().find(':input').each(function() {
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
		row = this;
		var selected = false;
		var deselect = $(this).find('.toggler .deselect');
		$(this).find(':input').each(function(i, e, row) {
			if($(e).attr('checked')) {
				$(deselect).addClass('active');
				selected = true;
				console.log('Should be true:');
				console.log(selected);
			}
		});
		console.log(deselect);
		console.log(selected);
		if(!selected) {
			$(deselect).removeClass('active');
		}
	}

});