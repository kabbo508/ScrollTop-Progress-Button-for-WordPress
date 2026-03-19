jQuery(function($){
	function initColorPickers(){
		$('.stpb-color-field').wpColorPicker({
			change: function(){ window.setTimeout(updatePreview, 1); },
			clear: function(){ window.setTimeout(updatePreview, 1); }
		});
	}

	function updatePreview(){
		var $wrap = $('.stpb-preview');
		if(!$wrap.length) return;

		var bg = $('input[name="stpb_settings[bg_color]"]').val() || $wrap.data('bg');
		var ic = $('input[name="stpb_settings[icon_color]"]').val() || $wrap.data('icon');
		var rg = $('input[name="stpb_settings[ring_color]"]').val() || $wrap.data('ring');
		var tr = $('input[name="stpb_settings[track_color]"]').val() || $wrap.data('track');
		var cls = $('input[name="stpb_settings[icon_class]"]').val() || $wrap.data('iconclass');

		$wrap.find('.stpb-btn').css({ background: bg, color: ic });
		$wrap.find('.stpb-btn i').attr('class', cls).css('color', ic);
		$wrap.find('.stpb-ring .progress').css('stroke', rg);
		$wrap.find('.stpb-ring .track').css('stroke', tr);
		$wrap.find('.stpb-icon-preview i').attr('class', cls);
	}

	$('.stpb-icon-select').on('change', function(){
		var val = $(this).val();
		if(val && val !== 'custom'){
			$('.stpb-icon-class').val(val);
			updatePreview();
		}
	});
	$('.stpb-icon-class').on('input', function(){ updatePreview(); });

	initColorPickers();
	updatePreview();
});
