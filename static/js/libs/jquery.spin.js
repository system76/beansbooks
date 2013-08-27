$.fn.spin = function(opts) {
  if( ! opts )
    var opts = {};
  opts.color = "#467F49";
  opts.lines = 15; // The number of lines to draw
  opts.length = 8; // The length of each line
  opts.width = 2; // The line thickness
  opts.radius = 5; // The radius of the inner circle
  this.each(function() {
    var $this = $(this),
        data = $this.data();

    if (data.spinner) {
      data.spinner.stop();
      delete data.spinner;
    }
    if (opts !== false) {
      data.spinner = new Spinner($.extend({color: $this.css('color')}, opts)).spin(this);
    }
  });
  return this;
};
