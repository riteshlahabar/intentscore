<script>
(function () {
    var money = function (value) {
        return '₹' + Math.round(value).toLocaleString('en-IN');
    };

    var formulas = {
        revenue: function (v) {
            var daily = v.groomers * v.appts * v.ticket;
            return {
                daily: money(daily),
                monthly: money(daily * v.days),
                annual: money(daily * v.days * 12),
                summary: money(daily * v.days) + ' per month'
            };
        },
        no_show: function (v) {
            var monthly = v.appts * (v.rate / 100) * v.ticket;
            return {
                monthly: money(monthly),
                annual: money(monthly * 12),
                summary: money(monthly) + ' lost per month'
            };
        },
        profit: function (v) {
            var costs = v.payroll + v.rent + v.supplies + v.software + v.marketing + v.other;
            var profit = v.revenue - costs;
            var margin = v.revenue > 0 ? (profit / v.revenue) * 100 : 0;
            return {
                monthly: money(profit),
                margin: margin.toFixed(1) + '%',
                annual: money(profit * 12),
                summary: money(profit) + ' per month'
            };
        }
    };

    document.querySelectorAll('[data-tool]').forEach(function (calc) {
        var tool = calc.dataset.tool;

        calc.querySelector('[data-calc]').addEventListener('click', function () {
            var values = {};
            calc.querySelectorAll('[data-in]').forEach(function (input) {
                values[input.dataset.in] = parseFloat(input.value) || 0;
            });

            var result = formulas[tool](values);
            var output = calc.querySelector('[data-calc-out]');

            output.querySelectorAll('[data-out]').forEach(function (el) {
                el.textContent = result[el.dataset.out] || '';
            });
            output.hidden = false;

            if (window.smartPageCalculated) {
                window.smartPageCalculated(tool, result.summary);
            }
        });
    });
})();
</script>
