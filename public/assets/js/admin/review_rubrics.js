document.addEventListener('DOMContentLoaded', function () {
  // EasyAdmin can render association widgets in slightly different ways
  // (select vs hidden input + TomSelect). Be flexible in how we locate them.
  const issueEl =
    document.getElementById('Review_issue') ||
    document.getElementById('Review_issue_autocomplete') ||
    document.querySelector('[name="Review[issue]"]') ||
    document.querySelector('[name="Review[issue][autocomplete]"]') ||
    document.querySelector('[data-ea-autocomplete-endpoint-url*="propertyName%5D=issue"]');
  const rubricEl =
    document.getElementById('Review_rubric') ||
    document.querySelector('[name="Review[rubric]"]') ||
    document.querySelector('[name="Review[rubric]"][type="hidden"]');

  if (!issueEl || !rubricEl) return;

  // EasyAdmin uses TomSelect for association fields.
  // We keep this robust in case EA falls back to plain <select>.
  function getTomSelect(el) {
    // @ts-ignore
    return el.tomselect || null;
  }

  function getOrWaitForTomSelect(el, callback, attempts = 20) {
    const ts = getTomSelect(el);
    if (ts || attempts <= 0) {
      callback(ts);
      return;
    }

    setTimeout(function () {
      getOrWaitForTomSelect(el, callback, attempts - 1);
    }, 100);
  }

  let issueTs = null;
  let rubricTs = getTomSelect(rubricEl);

  function disableRubric(placeholder) {
    if (rubricTs) {
      rubricTs.clear(true);
      rubricTs.clearOptions();
      rubricTs.disable();
      if (placeholder) rubricTs.settings.placeholder = placeholder;
      rubricTs.refreshOptions(false);
    } else {
      rubricEl.value = '';
      rubricEl.disabled = true;
      rubricEl.innerHTML = '';
      if (placeholder) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        rubricEl.appendChild(opt);
      }
    }
  }

  function enableRubric() {
    if (rubricTs) {
      rubricTs.enable();
    } else {
      rubricEl.disabled = false;
    }
  }

  async function loadRubricsForIssue(issueId) {
    if (!issueId) {
      disableRubric('Select an issue first');
      return;
    }

    const currentRubric = rubricTs ? rubricTs.getValue() : rubricEl.value;
    disableRubric('Loading rubrics…');

    try {
      const res = await fetch('/admin/ajax/review/rubrics?issueId=' + encodeURIComponent(issueId), {
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) {
        // Often this is a redirect to the login page or a 500
        const text = await res.text();
        throw new Error('HTTP ' + res.status + ': ' + text.slice(0, 200));
      }

      const data = await res.json();
      const rubrics = (data && data.rubrics) ? data.rubrics : [];

      if (rubricTs) {
        rubricTs.clearOptions();
        rubricTs.addOption(rubrics);
        rubricTs.settings.placeholder = rubrics.length ? '—' : 'No rubrics found';
        enableRubric();

        // Keep selection if still valid; otherwise clear.
        if (currentRubric && !rubrics.some(r => String(r.id) === String(currentRubric))) {
          rubricTs.clear(true);
        } else if (currentRubric) {
          rubricTs.setValue(currentRubric, true);
        }

        rubricTs.refreshOptions(false);
      } else {
        rubricEl.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '—';
        rubricEl.appendChild(empty);
        rubrics.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id;
          opt.textContent = r.text;
          rubricEl.appendChild(opt);
        });
        enableRubric();
        rubricEl.value = rubrics.some(r => String(r.id) === String(currentRubric)) ? currentRubric : '';
      }
    } catch (e) {
      console.error('Failed to load rubrics', e);
      disableRubric('Could not load rubrics');
    }
  }

  function getSelectedIssueId() {
    if (issueTs) {
      const v = issueTs.getValue();
      return v ? String(v) : '';
    }
    return issueEl.value ? String(issueEl.value) : '';
  }

  getOrWaitForTomSelect(issueEl, function (ts) {
    issueTs = ts;
    rubricTs = getTomSelect(rubricEl);

    // Initial load (edit page, or new page with prefilled issue_id)
    loadRubricsForIssue(getSelectedIssueId());

    // Live update when issue changes
    if (issueTs) {
      issueTs.on('change', function () {
        loadRubricsForIssue(getSelectedIssueId());
      });
      return;
    }

    issueEl.addEventListener('change', function () {
      loadRubricsForIssue(getSelectedIssueId());
    });
  });
});

