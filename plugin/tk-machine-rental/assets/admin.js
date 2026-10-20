jQuery(function ($) {
	'use strict';

	// 写真の選択（メディアライブラリ）
	var frame;
	$('#tkmr-image-select').on('click', function (e) {
		e.preventDefault();
		if (!frame) {
			frame = wp.media({
				title: tkmr.mediaTitle,
				button: { text: tkmr.mediaButton },
				library: { type: 'image' },
				multiple: false
			});
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
				$('#tkmr-image-id').val(a.id);
				$('#tkmr-image-preview').empty().append($('<img>').attr('src', url));
				$('#tkmr-image-remove').show();
			});
		}
		frame.open();
	});

	$('#tkmr-image-remove').on('click', function () {
		$('#tkmr-image-id').val('0');
		$('#tkmr-image-preview').empty();
		$(this).hide();
	});

	// 仕様の行の追加・削除
	$('#tkmr-spec-add').on('click', function () {
		$('#tkmr-spec-rows').append($('#tkmr-spec-template').html());
	});
	$(document).on('click', '.tkmr-spec-remove', function () {
		$(this).closest('tr').remove();
	});

	// 削除の確認（行の削除リンク）
	$(document).on('click', '.tkmr-delete', function (e) {
		if (!window.confirm(tkmr.confirmDelete)) {
			e.preventDefault();
		}
	});

	// 一括削除の確認
	$('#tkmr-list-form').on('submit', function (e) {
		var $form = $(this);
		var top = $form.find('select[name="action"]').val();
		var bottom = $form.find('select[name="action2"]').val();
		if ((top === 'delete' || bottom === 'delete') && $form.find('input[name="ids[]"]:checked').length) {
			if (!window.confirm(tkmr.confirmBulk)) {
				e.preventDefault();
			}
		}
	});
});
