document.addEventListener('DOMContentLoaded', function() {
    const mbidInput = document.getElementById('Artist_musicBrainzId');
    if (!mbidInput) return;

    // 1. Create Fetch Button
    const fetchButton = document.createElement('button');
    fetchButton.type = 'button';
    fetchButton.className = 'btn btn-secondary btn-sm';
    fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID using artist name';
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
            verifyLink.href = 'https://musicbrainz.org/artist/' + mbid;
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
        const nameInput = document.getElementById('Artist_name');
        const artistName = nameInput.value;

        if (!artistName) {
            alert('Please enter an artist name first.');
            return;
        }

        fetchButton.disabled = true;
        fetchButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Searching...';

        try {
            const response = await fetch('/admin/ajax/artist/search-musicbrainz?query=' + encodeURIComponent(artistName));
            
            if (!response.ok) {
                const errorText = await response.text();
                // Try to parse JSON error if possible
                try {
                    const jsonError = JSON.parse(errorText);
                    throw new Error(jsonError.error || `Server returned ${response.status}`);
                } catch (e) {
                    throw new Error(`Server returned ${response.status}: ${errorText}`);
                }
            }

            const data = await response.json();

            if (data.mbid) {
                mbidInput.value = data.mbid;
                updateVerifyState();
            } else {
                alert('No exact match found on MusicBrainz.');
            }
        } catch (error) {
            console.error('Full Error Object:', error);
            alert('An error occurred: ' + error.message);
        } finally {
            fetchButton.disabled = false;
            fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID';
        }
    });
});
