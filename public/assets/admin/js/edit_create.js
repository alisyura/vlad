document.addEventListener('DOMContentLoaded', function() {
//xdfkdjdsf вапвп
    tinymce.init({
        selector: '#postContent', // ID РІР°С€РµРіРѕ textarea
        plugins: 'advcode link image lists table code media fullscreen',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code | fullscreen',
        menubar: 'file edit view insert format tools table help',
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        file_picker_callback: function (cb, value, meta) {
            alert('Р¤СѓРЅРєС†РёРѕРЅР°Р» С„Р°Р№Р»РѕРІРѕРіРѕ РјРµРЅРµРґР¶РµСЂР° РїРѕРєР° РЅРµ СЂРµР°Р»РёР·РѕРІР°РЅ.');
        }
    });

    // Р”СЂСѓРіРёРµ СЃРєСЂРёРїС‚С‹ РґР»СЏ edit_create.js, РµСЃР»Рё РѕРЅРё РµСЃС‚СЊ
});