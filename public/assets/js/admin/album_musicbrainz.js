document.addEventListener('DOMContentLoaded', function() {
    const mbidInput = document.getElementById('Album_musicBrainzId');
    if (!mbidInput) return;

    // 1. Create Fetch Button
    const fetchButton = document.createElement('button');
    fetchButton.type = 'button';
    fetchButton.className = 'btn btn-secondary btn-sm';
    fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID using artist and title';
    fetchButton.style.marginLeft = '5px';
    
    // 2. Create Verify Link
    const verifyLink = document.createElement('a');
    verifyLink.className = 'btn btn-info btn-sm';
    verifyLink.innerHTML = '<i class="fa fa-external-link-alt"></i> Verify';
    verifyLink.target = '_blank';
    verifyLink.style.marginLeft = '5px';
    
    // Insert buttons after the input
    mbidInput.parentNode.appendChild(fetchButton);
    mbidInput.parentNode.appendChild(verifyLink);

    // State management function
    function updateVerifyState() {
        const mbid = mbidInput.value.trim();
        if (mbid) {
            verifyLink.classList.remove('disabled');
            verifyLink.removeAttribute('aria-disabled');
            verifyLink.href = 'https://musicbrainz.org/release-group/' + mbid;
        } else {
            verifyLink.classList.add('disabled');
            verifyLink.setAttribute('aria-disabled', 'true');
            verifyLink.removeAttribute('href');
        }
    }

    // Initialize state
    updateVerifyState();

    // Listen for manual changes
    mbidInput.addEventListener('input', updateVerifyState);

    // Fetch Button Click Handler
    fetchButton.addEventListener('click', async function() {
        const titleInput = document.getElementById('Album_title');
        const artistSelect = document.getElementById('Album_artist'); // Usually a select or autocomplete
        
        // EasyAdmin autocomplete handling
        // If it's an autocomplete, the actual value is in a hidden input, but the visible one is text.
        // However, standard AssociationField usually renders a select (if strict) or autocomplete.
        // Let's check typical EasyAdmin DOM.
        // If it is an autocomplete, the ID is usually in `Album_artist_autocomplete` (hidden) or similar? 
        // Actually, usually the ID is the value of the <select> or <input type="hidden">.
        
        let artistId = null;
        if (artistSelect) {
            if (artistSelect.tagName === 'SELECT') {
                 artistId = artistSelect.value;
            } else {
                // Check for tom-select or similar
                // If it is an autocomplete field, the structure is complex.
                // EasyAdmin often uses TomSelect.
                 if (artistSelect.tomselect) {
                     artistId = artistSelect.tomselect.getValue();
                 } else {
                     artistId = artistSelect.value;
                 }
            }
        }
        
        // Fallback for autocomplete hidden field if standard ID selection fails
        if (!artistId) {
             // Sometimes EA appends generated IDs. 
             // Let's assume for now it's a standard select or we can get the value.
             // If this fails, we might need to debug the DOM structure of EA AssociationField.
        }

        const albumTitle = titleInput ? titleInput.value : '';

        if (!albumTitle) {
            alert('Please enter an album title first.');
            return;
        }

        if (!artistId) {
            alert('Please select an artist first.');
            return;
        }

        fetchButton.disabled = true;
        fetchButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Searching...';

        try {
            const response = await fetch('/admin/ajax/album/search-musicbrainz?artistId=' + encodeURIComponent(artistId) + '&albumTitle=' + encodeURIComponent(albumTitle));
            
            if (!response.ok) {
                const data = await response.json();
                let msg = data.error || `Server returned ${response.status}`;
                if (data.api_url) {
                    msg += `\n\nAPI URL used: ${data.api_url}`;
                    const errorLink = document.createElement('div');
                    errorLink.innerHTML = `<a href="${data.api_url}" target="_blank" class="text-danger">Debug API Query</a>`;
                    fetchButton.parentNode.appendChild(errorLink);
                    setTimeout(() => errorLink.remove(), 10000);
                }
                throw new Error(msg);
            }

            const data = await response.json();

            if (data.mbid) {
                mbidInput.value = data.mbid;
                updateVerifyState();
            } else {
                alert('No exact match found on MusicBrainz for this artist/album combination.');
            }
        } catch (error) {
            console.error('Full Error Object:', error);
            alert(error.message);
        } finally {
            fetchButton.disabled = false;
            fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID using title';
        }
    });
});

