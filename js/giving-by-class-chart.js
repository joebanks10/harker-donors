// Bar chart module
var BarChart = (function($) {
  
  "use strict";

  var svg;

  var init = function(options) {
    var defaults = {
      selector: "svg",
      data: "data.tsv"
    };

    var settings = Object.assign({}, defaults, options);

    svg = d3.select(settings.selector);

    var margin = {top: 20, right: 20, bottom: 60, left: 40},
        width = +svg.attr("width") - margin.left - margin.right,
        height = +svg.attr("height") - margin.top - margin.bottom;

    var tip = d3.tip().attr('class', 'd3-tip n').offset([-40, 0]).html(function(d) {
      return 'Class of ' + d.class + ': ' + Math.round(d.gave_percent * 100) + '% (' + d.gave_count + '/' + d.student_count + ')';
    });
    var isShowingTip = -1;

    var x = d3.scaleBand().rangeRound([0, width]).padding(0.1),
        y = d3.scaleLinear().rangeRound([height, 0]);

    var g = svg.append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    d3.json(settings.data, function(error, data) {
      if (error || !data || data.constructor !== Array) {
        svg.remove();

        return;
      }

      data = data.map(function(d) {
        d.gave_percent = +d.gave_percent;
        return d;
      });

      x.domain(data.map(function(d) { return d.class; }));
      y.domain([0, 1]);
      // y.domain([0, d3.max(data, function(d) { return d.gave_percent; })]);

      g.call(tip);

      g.append("g")
          .attr("class", "axis axis--x")
          .attr("transform", "translate(0," + height + ")")
          .call(d3.axisBottom(x))
        .append("text")
          .attr("x", Math.floor(width/2))
          .attr("y", 40)
          .attr("fill", "#000")
          .text("Class Years");

      g.append("g")
          .attr("class", "axis axis--y")
          .call(d3.axisLeft(y).ticks(10, "%").tickSize(-width).tickSizeOuter(0))
        .append("text")
          .attr("transform", "rotate(-90)")
          .attr("x", -10)
          .attr("y", 6)
          .attr("dy", "0.71em")
          .attr("text-anchor", "end")
          .attr("fill", "#000")
          .text("Gave / Pledge");

      g.selectAll(".bar")
        .data(data)
        .enter().append("rect")
          .attr("class", "bar")
          .attr("x", function(d) { return x(d.class); })
          .attr("width", x.bandwidth())
          .attr("y", height)
          .attr("height", 0)
          .on('mouseover', tip.show)
          .on('mouseout', tip.hide)
          .on('touchstart', function(d, i) {
            if (isShowingTip === i) {
              tip.hide(d);
              isShowingTip = -1;
            } else {
              tip.show(d);
              isShowingTip = i;
            }
          })
          .transition()
          .duration(2000)
          .delay(function(d, i) { return 250 + i*50; })
          .ease(d3.easePolyOut)
          .attr("y", function(d) { return y(d.gave_percent); })
          .attr("height", function(d) { return height - y(d.gave_percent); });
    });

    bindEvents(settings);
  };

  var bindEvents = function(settings) {
    var svg = $(settings.selector);
    var aspect = svg.width() / svg.height();
    var container = svg.parent();

    $(window).on("resize", function() {
        var targetWidth = container.width();

        svg.attr("width", targetWidth);
        svg.attr("height", Math.round(targetWidth / aspect));
    }).trigger("resize");
  }

  return {
    init: init
  }

})(jQuery);


// Object.assign polyfill
if (typeof Object.assign != 'function') {
  (function () {
    Object.assign = function (target) {
      'use strict';
      // We must check against these specific cases.
      if (target === undefined || target === null) {
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var output = Object(target);
      for (var index = 1; index < arguments.length; index++) {
        var source = arguments[index];
        if (source !== undefined && source !== null) {
          for (var nextKey in source) {
            if (source.hasOwnProperty(nextKey)) {
              output[nextKey] = source[nextKey];
            }
          }
        }
      }
      return output;
    };
  })();
}


// Start
BarChart.init({
  selector: ".giving-by-class-chart",
  data: hkr_dnrs.ajax_url + "?action=hkr_dnrs_giving_by_class_data&school_year=" + hkr_dnrs.school_year
});
