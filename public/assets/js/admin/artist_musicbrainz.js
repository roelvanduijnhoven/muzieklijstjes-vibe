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

    // 3. Create Google Search Link
    const googleLink = document.createElement('a');
    googleLink.className = 'btn btn-secondary btn-sm';
    googleLink.innerHTML = '<i class="fab fa-google"></i> Google MB';
    googleLink.target = '_blank';
    googleLink.style.marginLeft = '5px';
    googleLink.title = 'Search artist on Google (site:musicbrainz.org)';
    
    // Insert buttons after the input
    mbidInput.parentNode.appendChild(fetchButton);
    mbidInput.parentNode.appendChild(verifyLink);
    mbidInput.parentNode.appendChild(googleLink);

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

    // Handle Artist Name changes for Google Link
    const nameInput = document.getElementById('Artist_name');
    function updateGoogleLink() {
        if (nameInput && nameInput.value) {
            const query = 'site:musicbrainz.org/artist ' + nameInput.value;
            googleLink.href = 'https://www.google.com/search?q=' + encodeURIComponent(query);
            googleLink.classList.remove('disabled');
            googleLink.removeAttribute('aria-disabled');
        } else {
            googleLink.removeAttribute('href');
            googleLink.classList.add('disabled');
            googleLink.setAttribute('aria-disabled', 'true');
        }
    }

    if (nameInput) {
        nameInput.addEventListener('input', updateGoogleLink);
        updateGoogleLink();
    }

    // Fetch Button Click Handler
    fetchButton.addEventListener('click', async function() {
        // nameInput is already defined above
        const artistName = nameInput ? nameInput.value : '';

        if (!artistName) {
            alert('Please enter an artist name first.');
            return;
        }

        fetchButton.disabled = true;
        fetchButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Searching...';

        try {
            const response = await fetch('/admin/ajax/artist/search-musicbrainz?query=' + encodeURIComponent(artistName));
            
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
                alert('No exact match found on MusicBrainz.');
            }
        } catch (error) {
            console.error('Full Error Object:', error);
            // Check if error is a json parsed error which might contain API URL
            let errorMsg = error.message;
            let apiUrl = null;
            
            // Try to extract URL if it was in the JSON error
            // The previous logic threw "Server returned X: {json}"
            // It's a bit hard to parse back.
            // Let's rely on modifying the 'if (!response.ok)' block above to pass data properly.
            // But since we are here, let's just use what we have or modify the JS structure slightly.
            // Actually, I can't easily access the parsed JSON from the catch block unless I throw it.
            // Let's restructure the try/catch.
            alert(error.message);
        } finally {
            fetchButton.disabled = false;
            fetchButton.innerHTML = '<i class="fa fa-search"></i> Fetch ID using artist name';
        }
    });
});
