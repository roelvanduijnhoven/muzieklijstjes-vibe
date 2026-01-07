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
        
        // Attempt to find the artist input using multiple strategies
        let artistInput = document.getElementById('Album_albumArtists'); 
        
        // Fallback: try finding by class
        if (!artistInput) {
            artistInput = document.querySelector('.js-album-artists-select');
        }
        
        // Fallback: try finding by name attribute
        if (!artistInput) {
            artistInput = document.querySelector('input[name="Album[albumArtists]"]');
        }

        let artistId = null;

        if (artistInput) {
            // Check if TomSelect is initialized
            // Note: TomSelect might hide the original input and use its own container.
            // But checking .tomselect property on the original input usually works.
            
            // Try to find the TomSelect instance on the input
            // @ts-ignore
            if (artistInput.tomselect) {
                 // @ts-ignore
                 const tsValue = artistInput.tomselect.getValue();
                 // getValue() returns string "1,2,3" for text input mode
                 if (tsValue) {
                     // If multiple items, it might be an array or string depending on config.
                     // For 'text' input based TomSelect, it updates the input value and getValue returns that string.
                     const val = String(tsValue);
                     const parts = val.split(',');
                     if (parts.length > 0 && parts[0]) {
                         artistId = parts[0];
                     }
                 }
            } else {
                // Fallback to raw input value
                const val = artistInput.value;
                if (val) {
                    const parts = val.split(',');
                    if (parts.length > 0 && parts[0]) {
                        artistId = parts[0];
                    }
                }
            }
        } else {
             // Debug: check if we can find any input with that ID
             console.error('Element #Album_albumArtists not found in DOM.');
        }
        
        // Debugging
        if (!artistId) {
             console.warn('Could not find artist ID.', {
                 foundInput: !!artistInput,
                 inputValue: artistInput ? artistInput.value : null,
                 tomSelectValue: (artistInput && artistInput.tomselect) ? artistInput.tomselect.getValue() : 'no-instance'
             });
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
            
            let data;
            try {
                data = await response.json();
            } catch (e) {
                // If parsing fails (e.g. HTML error page), we'll handle it based on status
                if (!response.ok) {
                     throw new Error(`Server returned ${response.status}`);
                }
                throw e; // If it was 200 but bad JSON, rethrow
            }

            if (!response.ok) {
                let msg = data.error || `Server returned ${response.status}`;
                
                // Remove existing helper links
                const existingLinks = fetchButton.parentNode.querySelectorAll('.mb-helper-link');
                existingLinks.forEach(el => el.remove());

                if (data.web_url) {
                    const searchLink = document.createElement('div');
                    searchLink.className = 'mb-helper-link mt-1';
                    searchLink.innerHTML = `<a href="${data.web_url}" target="_blank" class="text-primary"><i class="fa fa-external-link-alt"></i> Search on MusicBrainz manually</a>`;
                    fetchButton.parentNode.appendChild(searchLink);
                } else if (data.api_url) {
                    msg += `\n\nAPI URL used: ${data.api_url}`;
                    const errorLink = document.createElement('div');
                    errorLink.className = 'mb-helper-link';
                    errorLink.innerHTML = `<a href="${data.api_url}" target="_blank" class="text-danger">Debug API Query</a>`;
                    fetchButton.parentNode.appendChild(errorLink);
                    setTimeout(() => errorLink.remove(), 10000);
                }
                throw new Error(msg);
            }

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
            fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID using artist and title';
        }
    });
});

