document.addEventListener('DOMContentLoaded', function() {
    const artistSelects = document.querySelectorAll('.js-album-artists-select');
    
    artistSelects.forEach(function(input) {
        // Destroy existing instance if any (though unlikely for TextType)
        if (input.tomselect) {
            input.tomselect.destroy();
        }

        const initialOptions = JSON.parse(input.dataset.initialOptions || '[]');
        const searchUrl = input.dataset.searchUrl;

        // Populate options from initial data
        // For TextType, TomSelect expects value to be set on the input.
        // We need to add options so TomSelect knows about them.
        
        new TomSelect(input, {
            plugins: ['drag_drop', 'remove_button'],
            persist: false,
            create: false,
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            options: initialOptions,
            items: initialOptions.map(o => o.id), // Select the items
            load: function(query, callback) {
                if (!query.length) return callback();
                
                fetch(searchUrl + '?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            // Maintain order
            onItemAdd: function() {
                this.refreshOptions();
            },
        });
    });
});
