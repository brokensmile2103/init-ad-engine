jQuery(document).ready(function ($) {
    // Upload media logic...
    $(document).on('click', '.upload-image-button', function (e) {
        e.preventDefault();
        const button = $(this);
        const input = button.prev('input');
        const frame = wp.media({
            title: 'Choose an image',
            button: { text: 'Use this image' },
            multiple: false
        });
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
        });
        frame.open();
    });

    // Auto-resize textarea on input
    function autoResizeTextarea($textarea) {
        $textarea.css('height', 'auto');
        $textarea.css('height', $textarea.prop('scrollHeight') + 'px');
    }

    $('#global_head_code, #global_footer_code').each(function () {
        autoResizeTextarea($(this)); // resize on page load
    }).on('input', function () {
        autoResizeTextarea($(this)); // resize on typing
    });
});
