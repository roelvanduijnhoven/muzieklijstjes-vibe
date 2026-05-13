(function () {
    function getCell(row, key) {
        return row.querySelector('[data-sort-key="' + key + '"]');
    }

    function parseNumber(value) {
        var normalizedValue = String(value).replace(',', '.');
        var number = parseFloat(normalizedValue);

        return Number.isNaN(number) ? null : number;
    }

    function compareValues(left, right, type, direction) {
        var leftIsEmpty = left === '';
        var rightIsEmpty = right === '';

        if (leftIsEmpty && rightIsEmpty) {
            return 0;
        }

        if (leftIsEmpty) {
            return 1;
        }

        if (rightIsEmpty) {
            return -1;
        }

        if (type === 'number') {
            var leftNumber = parseNumber(left);
            var rightNumber = parseNumber(right);

            if (leftNumber !== null && rightNumber !== null && leftNumber !== rightNumber) {
                return direction === 'asc' ? leftNumber - rightNumber : rightNumber - leftNumber;
            }
        }

        var comparison = left.localeCompare(right, undefined, { numeric: true, sensitivity: 'base' });

        return direction === 'asc' ? comparison : -comparison;
    }

    function updateIndicators(table, activeButton, direction) {
        table.querySelectorAll('.review-sort-indicator').forEach(function (indicator) {
            indicator.innerHTML = '&varr;';
            indicator.classList.add('text-muted');
        });

        var indicator = activeButton.querySelector('.review-sort-indicator');
        if (indicator) {
            indicator.innerHTML = direction === 'asc' ? '&uarr;' : '&darr;';
            indicator.classList.remove('text-muted');
        }
    }

    function getInitialDirection(type) {
        return type === 'number' ? 'desc' : 'asc';
    }

    function sortTable(table, button, forcedDirection) {
        var key = button.dataset.reviewSortKey;
        var type = button.dataset.reviewSortType || 'text';
        var currentDirection = button.dataset.reviewSortDirection;
        var direction = forcedDirection || (currentDirection ? (currentDirection === 'asc' ? 'desc' : 'asc') : getInitialDirection(type));
        var tbody = table.querySelector('tbody');
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        rows.sort(function (leftRow, rightRow) {
            var leftCell = getCell(leftRow, key);
            var rightCell = getCell(rightRow, key);
            var leftValue = leftCell ? leftCell.dataset.sortValue || '' : '';
            var rightValue = rightCell ? rightCell.dataset.sortValue || '' : '';

            return compareValues(leftValue, rightValue, type, direction);
        });

        rows.forEach(function (row) {
            tbody.appendChild(row);
        });

        table.querySelectorAll('[data-review-sort-key]').forEach(function (sortButton) {
            delete sortButton.dataset.reviewSortDirection;
        });

        button.dataset.reviewSortDirection = direction;
        updateIndicators(table, button, direction);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-review-table]').forEach(function (table) {
            table.querySelectorAll('[data-review-sort-key]').forEach(function (button) {
                button.addEventListener('click', function () {
                    sortTable(table, button);
                });
            });

            var defaultButton = table.dataset.defaultSortKey
                ? table.querySelector('[data-review-sort-key="' + table.dataset.defaultSortKey + '"]')
                : table.querySelector('[data-review-sort-key]');

            if (defaultButton) {
                sortTable(table, defaultButton, table.dataset.defaultSortDirection || getInitialDirection(defaultButton.dataset.reviewSortType || 'text'));
            }
        });
    });
})();
