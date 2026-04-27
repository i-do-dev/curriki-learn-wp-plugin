jQuery(document).ready(function ($) {
    const data = typeof ibSummaryCircularData !== 'undefined' ? ibSummaryCircularData : null;

    if (!data) {
        console.error('ibSummaryCircularData not found.');
        return;
    }
    if (data.interactions_float !== null) {
        $('.score-progress').circleProgress({
            startAngle: -Math.PI / 4 * 1,
            value: data.marks_float,
            size: 50,
            thickness: 6,
            lineCap: 'round',
            fill: {gradient: ['#0e7c57']}
        });
        $('.book-progress').circleProgress({
            startAngle: -Math.PI / 4 * 1,
            value: data.progress_float,
            size: 50,
            thickness: 6,
            lineCap: 'round',
            fill: {gradient: ['#1768c4']}
        });
        $('.interaction-progress').circleProgress({
            startAngle: -Math.PI / 4 * 1,
            value: data.interactions_float,
            size: 50,
            thickness: 6,
            lineCap: 'round',
            fill: {gradient: ['#1768c4']}
        });
    }
});