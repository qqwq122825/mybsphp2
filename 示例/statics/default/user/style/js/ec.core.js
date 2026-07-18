/**
 * Base ec.ui ec.lang ec.util jquery.plugin ec.form ec.template Date: 2012-08-31
 * 10:19:16
 */
window.ec || (window.ec = {});
ol.Cache = {
	_cache : {},
	_size : 0,
	set : function(b, a) {
		if (!ol.Cache.contains(b)) {
			ol.Cache._size++;
		}
		ol.Cache._cache[b] = a;
		return a;
	},
	get : function(d, a) {
		var b = ol.Cache;
		var c = b._cache[d];
		if (c) {
			return c;
		}
		if (typeof (a) == "function") {
			c = a();
			b._cache[d] = c;
		} else {
			if (a) {
				c = a;
				b._cache[d] = c;
			} else {
				c = jQuery(d);
				b._cache[d] = c;
			}
		}
		b._size++;
		return c;
	},
	remove : function(a) {
		ol.Cache._size--;
		ol.Cache._cache[a] = null;
	},
	contains : function(a) {
		return ol.Cache._cache[a];
	}
};

ol.Plugin = {
	if_jquery : function(a, b) {
		try {
			var c = arguments.callee;
			b = b || 2;
			if (typeof (jQuery) != "undefined") {
				a(jQuery);
			} else {
				if (b == 0) {
					return;
				}
				setTimeout(function() {
					c(a, b - 1);
				}, 1000);
			}
		} catch (d) {
			if (ol.debug) {
				throw d;
			}
		}
	},
	if_jsapi : function(a) {
		try {
			if (typeof (ol) != "undefined" && typeof (ol.ready) != "undefined") {
				a(ol);
			}
		} catch (b) {
			if (ol.debug) {
				throw b;
			}
		}
	}
};

if (ol.isIE6 || ol.isIE7) {
	document.nativeGetElementById = document.getElementById;
	document.getElementById = function(c) {
		var b = document.nativeGetElementById(c);
		if (b) {
			if (b.attributes.id.value == c) {
				return b;
			} else {
				for ( var a = 1; a < document.all[c].length; a++) {
					if (document.all[c][a].attributes.id.value == c) {
						return document.all[c][a];
					}
				}
			}
		}
		return null;
	};
}

if (ol.isIE6) {
	try {
		document.execCommand("BackgroundImageCache", false, true);
	} catch (e) {
	}
}

// 创建ui对象
ol.pkg("ol.ui");

(function() {
	var b = function() {
		var c = {};
		c.width = ol.ui.masker._bwidth;
		c.height = ol.ui.masker._bheight;
		ol.Cache.get("ec_mask").css(c);
	};
	var a = {
		css : {
			opacity : 0.2,
			background : "#000"
		}
	};
	ol.ui.masker = {
		isShown : false,
		mask : function(d) {
			var f = jQuery;
			var e = ol.ui.masker;
			if (e.isShown) {
				return;
			}
			d = f.extend(true, {}, a, d);
			var c = ol.Cache.get("ec_mask", function() {
				f(window).resize(function() {
					if (ol.ui.masker.isShown) {
						return;
					}
					ol.Cache.get("ec_mask").css({
						width : e._bwidth(),
						height : e._bheight()
					});
				});
				return f("<div id='ec_mask' class='ec_mask'></div>").appendTo(
						"body").bgiframe();
			});
			e.isShown = true;
			d.css.width = e._bwidth();
			d.css.height = e._bheight();
			d.css.visibility = "visible";
			c.css(d.css);
			f(window).bind("resize", b);
		},
		unmask : function() {
			ol.ui.masker.isShown = false;
			ol.Cache.get("ec_mask").css({
				visibility : "hidden",
				width : 0,
				height : 0
			});
			jQuery(window).unbind("resize", b);
		},
		_bheight : function() {
			var e = jQuery;
			if (e.browser.msie && e.browser.version < 7) {
				var d = Math.max(document.documentElement.scrollHeight,
						document.body.scrollHeight);
				var c = Math.max(document.documentElement.offsetHeight,
						document.body.offsetHeight);
				if (d < c) {
					return e(window).height();
				} else {
					return d;
				}
			} else {
				return e(document).height();
			}
		},
		_bwidth : function() {
			var e = jQuery;
			if (e.browser.msie && e.browser.version < 7) {
				var c = Math.max(document.documentElement.scrollWidth,
						document.body.scrollWidth);
				var d = Math.max(document.documentElement.offsetWidth,
						document.body.offsetWidth);
				if (c < d) {
					return e(window).width();
				} else {
					return c;
				}
			} else {
				return e(document).width();
			}
		}
	};
})();

(function() {
	var a = {
		event : "hover",
		menu : null
	};
	ol.ui.dropdown = function(b, d) {
		var c = this;
		c.hideTimer = null;
		c._objItem;
		c._menuItem;
		c._onMenu = false;
		c._events = {};
		c._isShow = false;
		c._hideMenu = function() {
			c.hideTimer = setTimeout(c.hide, 100);
		};
		c.init = function() {
			var e = jQuery;
			d = e.extend({}, a, d);
			if (d.event == "hover") {
				d.event = "mouseover";
			} else {
				d.event = "click";
			}
			c._objItem = e(b);
			c._menuItem = d.menu ? e(d.menu) : c._objItem.next();
			c._objItem.unbind(d.event).bind(d.event, function(f) {
				clearTimeout(c.hideTimer);
				c.show();
			});
			c._menuItem.find(".dropdown_item").bind("click", function() {
				setTimeout(c.hide);
			});
		};
		c.bind = function(e, g, f) {
			c._menuItem.find(e).bind(g, f);
		};
		c.show = function() {
			switch (d.event) {
			case "mouseover":
				c._objItem.unbind("mouseout").bind("mouseout", function(e) {
					c._hideMenu();
					c._objItem.addClass("hover");
				});
				c._menuItem.unbind("mouseover").bind("mouseover", function() {
					clearTimeout(c.hideTimer);
					c._menuItem.css("display", "block");
				}).unbind("mouseout").bind("mouseout", function() {
					c._hideMenu();
				});
				break;
			case "click":
				if (c._isShow) {
					c.hide();
					return;
				} else {
					c._events.menu_click = function() {
						c._onMenu = true;
					};
					c._events.document_click = function(e) {
						if (e.button != 0) {
							return true;
						}
						if (c._onMenu === false) {
							c.hide();
						}
						c._onMenu = false;
					};
					setTimeout(function() {
						c._menuItem.bind("click", c._events.menu_click);
						jQuery(document)
								.bind("click", c._events.document_click);
					}, 1);
				}
				break;
			}
			c._objItem.addClass("hover");
			c._menuItem.css("display", "block");
			c._isShow = true;
		};
		c.hide = function() {
			if (c._events.document_click) {
				jQuery(document).unbind("click", c._events.document_click);
			}
			if (c._events.menu_click) {
				c._menuItem.unbind("click", c._events.menu_click);
			}
			c._objItem.removeClass("hover");
			c._menuItem.hide();
			c._isShow = false;
			c._onMenu = false;
		};
		this.init();
	};
})();

ol.ui.scrollTo = function(g, d) {
	d = d || {
		offsetY : 0
	};
	if (typeof (g) != "object") {
		var h = g.toString().substr(0, 1);
		if (!(h == "#" || h == ".")) {
			g = "#" + g;
		}
		g = jQuery(g);
	}
	if (g.length == 0) {
		return;
	}
	var b = g.offset().top;
	var f = (document.documentElement && !/webkit/ig.test(navigator.userAgent) ? document.documentElement
			: document.body);
	var e = jQuery(window).height();
	var a = f.scrollTop;
	if (!(a < b && b < a + e)) {
		f.scrollTop = b - d.offsetY;
	}
};

(function() {
	var b = {
		white : {
			opacity : 0.55,
			background : "#fff"
		},
		black : {
			opacity : 0.2,
			background : "#000"
		}
	};
	var a = {
		selector : "#ec_ui_loading",
		css : null,
		modal : true,
		maskConfig : null
	};
	ol.ui.loading = {
		options : null,
		show : function(d) {
			var f = ol.ui.loading;
			if (typeof (d) == "string") {
				d = {
					maskConfig : {
						css : b[d]
					}
				};
			}
			f.options = jQuery.extend(true, {}, a, d);
			if (f.options.modal) {
				ol.ui.masker.mask(f.options.maskConfig);
			}
			var c = jQuery(f.options.selector);
			if (c.length == 0) {
				c = jQuery(
						'<div id="ec_ui_loading" class="ec_ui_loading"></div>')
						.appendTo("body");
			}
			c.css(f.options.css);
			if (ol.isIE6 || ol.isIE && document.compatMode === "BackCompat") {
				var g = jQuery(window), h = g.scrollTop(), e = g.scrollLeft();
				h += (g.height() - c.height()) / 2;
				e += (g.width() - c.width()) / 2;
				c.css({
					top : h,
					left : e
				});
			}
			c.show();
			c = null;
			f = null;
		},
		hide : function() {
			var c = ol.ui.loading.options;
			if (c) {
				if (c.modal) {
					ol.ui.masker.unmask();
				}
				jQuery(c.selector).hide();
			}
		}
	};
})();

(function() {
	var a = {
		row : "tr",
		colors : [ "#fff", "#f7f7f7" ],
		hover : "#e3f3bf",
		index : 1,
		remain : 0,
		alterNum : 1
	};
	ol.ui.alternation = function(b, c) {
		var d = jQuery;
		function e(h, g) {
			var f = this;
			this.container = d(h);
			this.data = {};
			this.rows = null;
			this.init = function() {
				this.rows = this.container.find(g.row);
				var l;
				var k;
				for ( var j = 0; j < this.rows.length; j++) {
					l = this.rows[j];
					l.setAttribute("alternation", j);
					k = l.getAttribute("group");
					this.data[j] = {
						group : k,
						backgroundColor : ""
					};
				}
			};
			this.bindColor = function() {
				if (!g.colors) {
					return;
				}
				var k = this.rows.length - g.remain;
				for ( var m = g.index; m < k;) {
					for ( var l = 0; l < g.colors.length && m < k; l++) {
						row = this.rows[m];
						row.style.backgroundColor = g.colors[l];
						this.data[m].backgroundColor = g.colors[l];
						m = m + g.alterNum;
					}
				}
			};
			this.bindEvent = function() {
				this.rows.unbind("mouseover").bind(
						"mouseover",
						function() {
							var j = d(this);
							var i = j.attr("alternation");
							var k = f.data[i];
							if (g.hover) {
								j.css("backgroundColor", g.hover);
							}
							if (k.group) {
								f.container.find(
										g.row + "[group=" + k.group + "]")
										.addClass("hover");
							}
						}).unbind("mouseout").bind(
						"mouseout",
						function() {
							var j = d(this);
							var i = j.attr("alternation");
							var k = f.data[i];
							if (g.hover) {
								j.css("backgroundColor", k.backgroundColor);
							}
							if (k.group) {
								f.container.find(
										g.row + "[group=" + k.group + "]")
										.removeClass("hover");
							}
						});
			};
			this.init();
			this.bindColor();
			this.bindEvent();
		}
		c = d.extend({}, a, c);
		d(b).each(function() {
			new e(this, c);
		});
	};
})();

(function() {
	var a = function(b, k, h, g) {
		if (b.nodeType === 3) {
			var e = b.data.match(k);
			if (e) {
				var c = document.createElement(h || "span");
				c.className = g || "ec_ui_highlight highlight";
				var j = b.splitText(e.index);
				j.splitText(e[0].length);
				var f = j.cloneNode(true);
				c.appendChild(f);
				j.parentNode.replaceChild(c, j);
				return 1;
			}
		} else {
			if ((b.nodeType === 1 && b.childNodes)
					&& !/(script|style)/i.test(b.tagName)
					&& !(b.tagName === h.toUpperCase() && b.className === g)) {
				for ( var d = 0; d < b.childNodes.length; d++) {
					d += a(b.childNodes[d], k, h, g);
				}
			}
		}
		return 0;
	};
	ol.ui.highlight = function(b, g, j) {
		var c = {
			className : null,
			element : "span",
			caseSensitive : false,
			wordsOnly : false
		};
		jQuery.extend(c, j);
		if (g.constructor === String) {
			g = [ g ];
		}
		g = jQuery.grep(g, function(l, k) {
			return l != "";
		});
		if (g.length == 0) {
			return this;
		}
		var h = g.join("|");
		h = h.replace(/([\\\$\{\}\(\)\[\]\+\?\-\>\<\^\!\.\*])/g, "\\$1");
		var f = c.caseSensitive ? "" : "i";
		var e = "(" + h + ")";
		if (c.wordsOnly) {
			e = "\\b" + e + "\\b";
		}
		var i = new RegExp(e, f);
		var d = b;
		if (typeof (b) == "string") {
			d = ol.Cache.get(b);
		}
		return d.each(function() {
			a(this, i, c.element, c.className);
		});
	};
})();

(function() {
	var c = {
		_ec_ui_alert : {
			_default : {
				title : "???????",
				zIndex : 399,
				showCancel : false,
				modal : false,
				draggable : false,
				focus : ".box-ok:first",
				width : 300,
				clickOut : function(d) {
					d.close();
				}
			}
		},
		_ec_ui_info : {},
		_ec_ui_warn : {},
		_ec_ui_error : {}
	};
	function b(h, d, g) {
		var f = ol.Cache.get;
		var e = f(d.boxid, function() {
			return new ol.box(null, d);
		});
		e.open(h);
		if (g.timeout) {
			if (c[d.boxid].timer) {
				clearTimeout(c[d.boxid].timer);
			}
			c[d.boxid].timer = setTimeout(function() {
				if (ol.isIE6) {
					e.close();
				} else {
					e.fadeOut(800);
				}
			}, g.timeout);
			f("#" + d.boxid).unbind("mouseover").bind("mouseover", function() {
				clearTimeout(c[d.boxid].timer);
			}).unbind("mouseout").bind("mouseout", function() {
				c[d.boxid].timer = setTimeout(function() {
					if (ol.isIE6) {
						e.close();
					} else {
						e.fadeOut(800);
					}
				}, g.timeout / 2);
			});
		}
	}
	var a = {
		title : "???????",
		zIndex : 399,
		showButton : false,
		modal : false,
		draggable : false,
		focus : ".box-close",
		width : 300
	};
	ol.ui.warn = function(f, d) {
		d = jQuery.extend({}, a, d);
		d.boxid = "_ec_ui_warn";
		d.boxclass = "ec_ui_warn";
		var e = {
			timeout : d.timeout
		};
		delete d.timeout;
		b(f, d, e);
	};
	ol.ui.info = function(f, d) {
		d = jQuery.extend({}, a, d);
		d.boxid = "_ec_ui_info";
		d.boxclass = "ec_ui_info";
		var e = {
			timeout : d.timeout
		};
		delete d.timeout;
		b(f, d, e);
	};
	ol.ui.error = function(f, d) {
		d = jQuery.extend({}, a, d);
		d.boxid = "_ec_ui_error";
		d.boxclass = "ec_ui_error";
		var e = {
			timeout : d.timeout
		};
		delete d.timeout;
		b(f, d, e);
	};
	ol.ui.alert = function(f, d) {
		d = jQuery.extend({}, c._ec_ui_alert._default, d);
		d.boxid = "_ec_ui_alert";
		d.boxclass = "ec_ui_alert";
		var e = ol.Cache.get(d.boxid, function() {
			return new ol.box(null, d);
		});
		e.setTitle(d.title);
		e.open(f);
	};
})();

(function() {
	var a = {
		style : "style1",
		offsetX : 0,
		offsetY : 0
	}, b = '<div class="ec_ui_ballon"><div id="ballon_header"></div><div id="ballon_body"></div><div id="ballon_footer"></div></div>';
	ol.ui.ballon = function(c, h, d) {
		d = jQuery.extend({}, a, d);
		var f = jQuery(c);
		var g = jQuery(b);
		var e;
		g.find("#ballon_body").html(h || "");
		f.mouseover(function() {
			e = setTimeout(function() {
				var i = d.offsetX || 0;
				var l = d.offsetY || 0;
				var k = f.offset().top + f.height();
				var j = f.offset().left;
				j = Math.max(j + i, 0);
				k = Math.max(k + l, 0);
				g.css({
					display : "block",
					top : k,
					left : j
				}).addClass(d.style);
				g.appendTo("body").bgiframe();
			}, 250);
		}).mouseout(function() {
			clearTimeout(e);
			g.remove();
		});
		return ol.ui;
	};
})();
(function() {
	var a = {
		css : null,
		captureInput : false
	};
	ol.ui.hover = function(c, b) {
		var d = jQuery;
		b = d.extend(true, {}, a, b);
		d(c).each(
				function() {
					var j = null;
					var f = false;
					var e = false;
					var i = d(this);
					var g = function() {
						if (f || e) {
							return;
						}
						if (b.css) {
							j = i.attr("style");
							i.css(b.css);
						} else {
							i.addClass("hover");
						}
					};
					var h = function() {
						if (f || e) {
							return;
						}
						if (b.css) {
							i.attr("style", j);
						} else {
							i.removeClass("hover");
						}
					};
					i.mouseover(function() {
						g();
						e = true;
					}).mouseout(function() {
						e = false;
						h();
					});
					if (this.tagName == "INPUT" || this.tagName == "TEXTAREA") {
						i.focus(function() {
							g();
							f = true;
						}).blur(function() {
							f = false;
							h();
						});
					} else {
						if (b.captureInput) {
							i.find("input[type=text],textarea").bind("focus",
									function() {
										g();
										f = true;
									}).bind("blur", function() {
								f = false;
								h();
							});
						}
					}
				});
		return ol.ui;
	};
})();

ol.pkg("ol.lang");
if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(e, h) {
		var b, a, g = "\0";
		if (h != null) {
			b = this.slice(h);
			a = h;
		} else {
			b = this;
			a = 0;
		}
		var f = g + b.join(g) + g, c = f.indexOf(g + e + g);
		if (c == -1) {
			return -1;
		}
		a += f.slice(0, c).replace(/[^\0]/g, "").length;
		return a;
	};
}
Array.prototype.clone = function() {
	return this.slice(0);
};
String.prototype.endWith = function(a) {
	if (a == null || a == "" || this.length == 0 || a.length > this.length) {
		return false;
	}
	if (this.substring(this.length - a.length) == a) {
		return true;
	} else {
		return false;
	}
	return true;
};
String.prototype.startWith = function(a) {
	if (a == null || a == "" || this.length == 0 || a.length > this.length) {
		return false;
	}
	if (this.substr(0, a.length) == a) {
		return true;
	} else {
		return false;
	}
	return true;
};
if (!String.prototype.trim) {
	String.prototype.trim = function() {
		return this.replace(/^\s+/, "").replace(/\s+$/, "");
	};
}
String.prototype.len = function() {
	return this.replace(/[^\x00-\xff]/g, "aa").length;
};
String.prototype.replaceAll = function(b, a) {
	return this.replace(new RegExp(b, "gm"), a);
};
String.prototype.parseDate = function(m) {
	var c = {
		"\\." : {
			v : "\\."
		},
		"\\?" : {
			v : "\\?"
		},
		"M+" : {
			v : "(0[1-9]|1[0-2]|[1-9])",
			k : "MM"
		},
		"d+" : {
			v : "(3[01]|[12][0-9]|0[1-9]|[1-9])",
			k : "dd"
		},
		"y+" : {
			v : "(\\d{4})",
			k : "yyyy"
		},
		"H+" : {
			v : "(2[0-3]|[01][0-9]|[0-9])",
			k : "HH"
		},
		"m+" : {
			v : "([0-5][0-9]|[0-9])",
			k : "mm"
		},
		"s+" : {
			v : "([0-5][0-9]|[0-9])",
			k : "ss"
		},
		S : {
			v : "(\\d+)",
			k : "S"
		}
	};
	var j = [];
	var e = m;
	var b;
	var g;
	for ( var f in c) {
		if ((b = m.search(new RegExp("(" + f + ")"))) != -1) {
			g = c[f];
			e = e.replace(RegExp.$1, g.v);
			if (g.k) {
				j.push({
					n : g.k,
					order : b
				});
			}
		}
	}
	j.sort(function(i, d) {
		return i.order - d.order;
	});
	g = {};
	for ( var h = 0; h < j.length; h++) {
		g[j[h].n] = h + 1;
	}
	var a = this.match(new RegExp(e));
	if (!a) {
		throw "Invalid String for parse to Date!";
	}
	var l = new Date();
	if (g.yyyy) {
		l.setFullYear(a[g.yyyy]);
	}
	if (g.dd) {
		var n = a[g.dd];
		l.setDate(n);
		l.setDate(n);
	} else {
		l.setDate(1);
		l.setDate(1);
	}
	if (g.MM) {
		l.setMonth(a[g.MM] - 1);
	}
	if (g.HH) {
		l.setHours(a[g.HH]);
	} else {
		l.setHours(0);
	}
	if (g.mm) {
		l.setMinutes(a[g.mm]);
	} else {
		l.setMinutes(0);
	}
	if (g.ss) {
		l.setSeconds(a[g.ss]);
	} else {
		l.setSeconds(0);
	}
	if (g.S) {
		l.setMilliseconds(a[g.S]);
	} else {
		l.setMilliseconds(0);
	}
	return l;
};
Date.prototype.format = function(a) {
	var d = {
		"M+" : this.getMonth() + 1,
		"d+" : this.getDate(),
		"h+" : this.getHours() % 12 == 0 ? 12 : this.getHours() % 12,
		"H+" : this.getHours(),
		"m+" : this.getMinutes(),
		"s+" : this.getSeconds(),
		"q+" : Math.floor((this.getMonth() + 3) / 3),
		S : this.getMilliseconds()
	};
	var c = {
		"0" : "\u65e5",
		"1" : "\u4e00",
		"2" : "\u4e8c",
		"3" : "\u4e09",
		"4" : "\u56db",
		"5" : "\u4e94",
		"6" : "\u516d"
	};
	if (/(y+)/.test(a)) {
		a = a.replace(RegExp.$1, (this.getFullYear() + "")
				.substr(4 - RegExp.$1.length));
	}
	if (/(E+)/.test(a)) {
		a = a
				.replace(
						RegExp.$1,
						((RegExp.$1.length > 1) ? (RegExp.$1.length > 2 ? "\u661f\u671f"
								: "\u5468")
								: "")
								+ c[this.getDay() + ""]);
	}
	for ( var b in d) {
		if (new RegExp("(" + b + ")").test(a)) {
			a = a.replace(RegExp.$1, (RegExp.$1.length == 1) ? (d[b])
					: (("00" + d[b]).substr(("" + d[b]).length)));
		}
	}
	return a;
};
if (!
/* @cc_on!@ */
0 && window.JSON && window.JSON.parse && window.JSON.stringify) {
	ol.lang.json = (function() {
		var a = /___$/;
		return {
			parse : function(c) {
				try {
					return window.JSON.parse(c);
				} catch (b) {
					return false;
				}
			},
			stringify : function(c) {
				try {
					return window.JSON.stringify(c, function(d, e) {
						return !a.test(d) ? e : null;
					});
				} catch (b) {
					return null;
				}
			}
		};
	})();
} else {
	ol.lang.json = function() {
		function f(n) {
			return n < 10 ? "0" + n : n;
		}
		Date.prototype.toJSON = function() {
			return [ this.getUTCFullYear(), "-", f(this.getUTCMonth() + 1),
					"-", f(this.getUTCDate()), "T", f(this.getUTCHours()), ":",
					f(this.getUTCMinutes()), ":", f(this.getUTCSeconds()), "Z" ]
					.join("");
		};
		var m = {
			"\b" : "\\b",
			"\t" : "\\t",
			"\n" : "\\n",
			"\f" : "\\f",
			"\r" : "\\r",
			'"' : '\\"',
			"\\" : "\\\\"
		};
		function stringify(value) {
			var a, i, k, l, r = /["\\\x00-\x1f\x7f-\x9f]/g, v;
			switch (typeof value) {
			case "string":
				return r.test(value) ? '"'
						+ value.replace(r, function(a) {
							var c = m[a];
							if (c) {
								return c;
							}
							c = a.charCodeAt();
							return "\\u00" + Math.floor(c / 16).toString(16)
									+ (c % 16).toString(16);
						}) + '"' : '"' + value + '"';
			case "number":
				return isFinite(value) ? String(value) : "null";
			case "boolean":
			case "null":
				return String(value);
			case "object":
				if (!value) {
					return "null";
				}
				a = [];
				if (typeof value.length === "number"
						&& !value.propertyIsEnumerable("length")) {
					l = value.length;
					for (i = 0; i < l; i += 1) {
						a.push(stringify(value[i]) || "null");
					}
					return "[" + a.join(",") + "]";
				}
				for (k in value) {
					if (k.match("___$")) {
						continue;
					}
					if (value.hasOwnProperty(k)) {
						if (typeof k === "string") {
							v = stringify(value[k]);
							if (v) {
								a.push(stringify(k) + ":" + v);
							}
						}
					}
				}
				return "{" + a.join(",") + "}";
			}
		}
		return {
			stringify : stringify,
			parse : function(text) {
				if (/^[\],:{}\s]*$/
						.test(text
								.replace(/\\["\\\/b-u]/g, "@")
								.replace(
										/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
										"]")
								.replace(/(?:^|:|,)(?:\s*\[)+/g, ""))) {
					return eval("(" + text + ")");
				}
				return false;
			}
		};
	}();
}

ol.pkg("ol.util");
ol.util.trim = function(a) {
	if (a == null) {
		return "";
	}
	if (typeof (a) != "string") {
		return a;
	}
	return a.trim();
};
ol.util.left = function(d, a, c) {
	if (d.len() < a) {
		return d;
	}
	var e = 0;
	for ( var b = 0; b < d.length; b++) {
		if (d.charCodeAt(b) > 128) {
			e = e + 2;
		} else {
			e = e + 1;
		}
		if (e > a) {
			return d.substring(0, b) + (c ? c : "");
		}
	}
	return d;
};
ol.util.isEmpty = function(a) {
	if (ol.util.trim(a) == "") {
		return true;
	}
	return false;
};
ol.util.isDate = function(a) {
	if (a == null || a == "") {
		return false;
	}
	re = /\d{4}-{1}\d{2}-{1}\d{2}$/;
	return a.match(re);
};
ol.util.isNumeric = function(a) {
	strRef = "1234567890";
	if (a == "") {
		return false;
	}
	for (i = 0; i < a.length; i++) {
		tempChar = a.substring(i, i + 1);
		if (strRef.indexOf(tempChar, 0) == -1) {
			return false;
		}
	}
	return true;
};
ol.util.isFloat = function(b) {
	var a = /^[0-9]+.?[0-9]*$/;
	return a.test(s);
};
ol.util.isEmail = function(e, f) {
	if (!e) {
		return false;
	}
	var c = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9\-]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/i;
	var d = new RegExp(c);
	var a;
	if (f) {
		a = e.split(f);
	} else {
		a = [ e ];
	}
	for ( var b = 0; b < a.length; b++) {
		if (a[b].match(d) == null) {
			return false;
		}
	}
	return true;
};
ol.util.escapeHtml = function(a) {
	if (typeof (a) != "string") {
		return a;
	}
	if (ol.util.isEmpty(a)) {
		return "";
	}
	a = a.replaceAll("&", "&amp;");
	a = a.replaceAll('"', "&quot;");
	a = a.replaceAll(" ", "&nbsp;");
	a = a.replaceAll("<", "&lt;");
	a = a.replaceAll(">", "&gt;");
	a = a.replaceAll("'", "&#039;");
	a = a.replaceAll("\r\n", "<br/>");
	a = a.replaceAll("\n", "<br/>");
	a = a.replaceAll("\r", "<br/>");
	return a;
};
ol.util.unescapeHtml = function(a) {
	if (typeof (a) != "string") {
		return a;
	}
	if (ol.util.isEmpty(a)) {
		return "";
	}
	a = a.replaceAll("&quot;", '"');
	a = a.replaceAll("&nbsp;", " ");
	a = a.replaceAll("&lt;", "<");
	a = a.replaceAll("&gt;", ">");
	a = a.replaceAll("&#039;", "'");
	a = a.replaceAll("<br>", "\n");
	a = a.replaceAll("<br/>", "\n");
	a = a.replaceAll("&#61;", "=");
	a = a.replaceAll("&amp;", "&");
	return a;
};
ol.util.isFunction = function(a) {
	if (!a) {
		return false;
	}
	return Object.prototype.toString.call(a) === "[object Function]";
};
ol.util.isArray = function(a) {
	if (!a) {
		return false;
	}
	return Object.prototype.toString.call(a) === "[object Array]";
};
ol.util.isObject = function(a) {
	if (!a) {
		return false;
	}
	return Object.prototype.toString.call(a) === "[object Object]";
};

ol.util.cookie = {
	get : function(a) {
		var f = null;
		if (document.cookie && document.cookie != "") {
			var d = document.cookie.split(";");
			for ( var c = 0; c < d.length; c++) {
				var b = (d[c] || "")
						.replace(/^(\s|\u00A0)+|(\s|\u00A0)+$/g, "");
				if (b.substring(0, a.length + 1) == (a + "=")) {
					var e = function(j) {
						j = j.replace(/\+/g, " ");
						var h = '()<>@,;:\\"/[]?={}';
						for ( var g = 0; g < h.length; g++) {
							if (j.indexOf(h.charAt(g)) != -1) {
								if (j.startWith('"')) {
									j = j.substring(1);
								}
								if (j.endWith('"')) {
									j = j.substring(0, j.length - 1);
								}
								break;
							}
						}
						return decodeURIComponent(j);
					};
					f = e(b.substring(a.length + 1));
					break;
				}
			}
		}
		return f;
	},
	set : function(d, f, c) {
		c = c || {};
		if (f === null) {
			f = "";
			c.expires = -1;
		}
		var a = "";
		if (c.expires
				&& (typeof c.expires == "number" || c.expires.toUTCString)) {
			var b;
			if (typeof c.expires == "number") {
				b = new Date();
				b.setTime(b.getTime() + (c.expires * 24 * 60 * 60 * 1000));
			} else {
				b = c.expires;
			}
			a = "; expires=" + b.toUTCString();
		}
		var h = "; path=" + (c.path || "/");
		var e = c.domain ? "; domain=" + (c.domain) : "";
		var g = c.secure ? "; secure" : "";
		document.cookie = [ d, "=", encodeURIComponent(f), a, h, e, g ]
				.join("");
	},
	remove : function(a) {
		this.set(a, null);
	}
};
ol.Plugin
		.if_jquery(function(a) {
			ol.Plugin.if_jsapi(function(c) {
				ol._setLoadStatus("jquery.bgiframe", "complete");
			});
			a.fn.bgiframe = (a.browser.msie
					&& /msie 6\.0/i.test(navigator.userAgent) ? function(d) {
				d = a.extend({
					top : "auto",
					left : "auto",
					width : "auto",
					height : "auto",
					opacity : true,
					src : "javascript:false;"
				}, d);
				var c = '<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'
						+ d.src
						+ '"style="display:block;position:absolute;z-index:-1;'
						+ (d.opacity !== false ? "filter:Alpha(Opacity='0');"
								: "")
						+ "top:"
						+ (d.top == "auto" ? "expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+'px')"
								: b(d.top))
						+ ";left:"
						+ (d.left == "auto" ? "expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+'px')"
								: b(d.left))
						+ ";width:"
						+ (d.width == "auto" ? "expression(this.parentNode.offsetWidth+'px')"
								: b(d.width))
						+ ";height:"
						+ (d.height == "auto" ? "expression(this.parentNode.offsetHeight+'px')"
								: b(d.height)) + ';"/>';
				return this.each(function() {
					if (a(this).children("iframe.bgiframe").length === 0) {
						this.insertBefore(document.createElement(c),
								this.firstChild);
					}
				});
			}
					: function() {
						return this;
					});
			a.fn.bgIframe = a.fn.bgiframe;
			function b(c) {
				return c && c.constructor === Number ? c + "px" : c;
			}
		});
ol.Plugin.if_jquery(function(a) {
	a.fn.check = function(b) {
		switch (b) {
		case true:
			return this.each(function() {
				this.checked = true;
			});
			break;
		case false:
			return this.each(function() {
				this.checked = false;
			});
			break;
		default:
			return this.each(function() {
				this.checked = !this.checked;
			});
		}
	};
	a.fn.select = function(b) {
		return this.each(function() {
			a(this).val(b);
		});
	};
});

ol.pkg("ol.form.input");
(function() {
	var a = 0;
	ol.form.input.label = function(b, e, c) {
		if (!e) {
			throw ("Please set label text!");
			return ol.form.input;
		}
		var d = jQuery;
		c = d.extend({}, ol.form.input.label.defaults, c);
		d(b)
				.each(
						function() {
							var l = d(this), g = l.attr("id"), m = Number(l
									.css("z-index")) || 1, n = parseInt(l
									.css("margin-top"), 10)
									+ parseInt(l.css("border-top-width"), 10), i = parseInt(
									l.css("margin-left"), 10)
									+ parseInt(l.css("border-left-width"), 10), o = d('<label style="display:none;position:absolute;cursor:text;float:left;z-index:'
									+ (m + 1) + '"></label>'), 
									p=d('<div style="border:none;background:transparent;cursor:text;margin:2px;width:'+l[0].style.width+';" tabindex=-1>'+e+'</div>').attr("class",l.attr("class"));
							if (!g && c.autoId) {
								g = "input_label_" + (a++);
								l.attr("id", g);
							}
							o.attr("for", g);
							var j = {
								color : c.color
							};
							p.css(j);
							p[0].defaultValue = e;
							o.css({
								"padding-left" : i,
								"padding-top" : n
							}).append(p);
							l.css("z-index", m).before(o);
							var k = l.parents("form");
							if (k.length > 0) {
								k.bind("reset", function() {
									setTimeout(function() {
										h();
									}, 150);
								});
							}
							var h = function() {
								if (l.attr("value").length == 0) {
									o.css("display", "block");
								}
							}, q = function() {
								o.css("display", "none");
							}, k1 = function() {
								if (l.attr("value").length != 0) {
									o.css("display", "none");
								}
							}, k2 = function(event) {
								if (event.keyCode == 8
										&& l.attr("value").length == 0) {
									if (event.returnValue) {
										event.returnValue = false;
									}
									if (event.preventDefault) {
										event.preventDefault();
									}
								}
								o.css("display", "none");
								l.focus();
							};
							// l.bind("focus", q).bind("blur", h);
							// l.bind("focus", k1).bind("keydown", q).bind("blur",h);
							l.bind("focus", k1).bind("blur",h);
							o.bind("keydown", k2);
							var elmenttemp = document.activeElement|| document.currentTarget;
							if (elmenttemp != l[0]) {
								h();
							}
							
							var itv=setInterval(
									function(){
									    //there are font to hidden label，no font to show label
										if (l.attr("value").length != 0) {
										    o.css("display", "none");
										   // clearInterval(itv);
									      }
									  }
									,100);
						});
		return ol.form.input;
	};
})();
ol.form.input.label.defaults = {
	color : "#ccc",
	autoId : true
};
(function() {
	var b = {
		max : Number.MAX_VALUE,
		exceedCallback : null
	}, a = function(f) {
		var d = f.length, e = f.replace(/[^\x00-\xff]/g, "").length, c = (e % 2 == 0 ? e / 2
				: parseInt(e / 2) + 1)
				+ (d - e);
		return c;
	};
	ol.form.input.wordCount = function(c, d) {
		var e = jQuery;
		opt = e.extend({}, b, d);
		e(c).each(function(g) {
			if (typeof (opt.callback) == "function") {
				var f = a(this.value);
				opt.callback.call(this, f);
				if (f > opt.max && opt.exceedCallback) {
					opt.exceedCallback.call(this, f);
				}
			}
			e(this).bind("keyup", function() {
				var h = a(this.value);
				if (typeof (opt.callback) == "function") {
					opt.callback.call(this, h);
				}
				if (h > opt.max && opt.exceedCallback) {
					opt.exceedCallback.call(this, h);
				}
			});
		});
	};
})();

ol.pkg("ol.form.validator");
(function() {
	var d = function(m, l) {
		if (logger && logger.warn) {
			logger.warn(m, l);
		}
	}, k = function(m, l) {
		if (logger && logger.error) {
			logger.error(m, l);
		}
	}, h = {
		trim : true,
		validOnChange : false,
		allowEmpty : true,
		async : true,
		errorClass : null,
		successFunction : null,
		errorFunction : null
	};
	var a = {};
	var i = {};
	var j = function(q, t, m, n, s, l) {
		jQuery.extend(l, m);
		if (n) {
			s = i[n];
			if (!s) {
				d(n + " rule is undefined!");
				return true;
			}
		}
		var o;
		if (n) {
			l.type = n;
		}
		if (s.length == 3) {
			o = s(q, t, l);
		} else {
			o = s(q, l);
		}
		if (!o) {
			if (l.errorFunction) {
				var p = function() {
					if (m.errorClass && t) {
						t.addClass(m.errorClass);
					}
					l.errorFunction(t || q, l);
				};
				if (m.async) {
					setTimeout(p, 1);
				} else {
					p();
				}
			}
			return false;
		}
		return true;
	};
	var b = function(q, r, n, l) {
		if (!n.type) {
			if (!j(q, r, n, null, n.rule, l)) {
				return false;
			}
		} else {
			var o = n.type;
			if (typeof (n.type) == "string") {
				o = [ n.type ];
			}
			var p = {};
			for ( var m = 0; m < o.length; m++) {
				if (!p[o[m]]) {
					if (!j(q, r, n, o[m], null, l)) {
						return false;
					}
					p[o[m]] = 1;
				}
			}
		}
		return true;
	};
	var e = function(n) {
		var q = n.attr("validator");
		if (!q) {
			return true;
		}
		var m = a[q];
		var v;
		switch (n.attr("tagName")) {
		case "SELECT":
			v = n.val();
			break;
		default:
			v = n[0].value;
		}
		var s = {};
		var t = [];
		for ( var o = 0; o < m.length; o++) {
			var r = {};
			if (m[o].trim) {
				v = $.trim(v);
			}
			if (!b(v, n, m[o], r)) {
				return false;
			}
			t.push(r);
		}
		for ( var o = 0; o < m.length; o++) {
			var u = m[o];
			if (u.successFunction) {
				var l = t[o];
				var p = function() {
					if (u.errorClass && n) {
						n.removeClass(u.errorClass);
					}
					u.successFunction(n || v, l);
				};
				if (u.async) {
					// setTimeout(p, 1);
					p();
				} else {
					p();
				}
			}
		}
		return true;
	};
	ol.form.validator = function(p, n) {
		if (typeof (n) == "object") {
			var q = {};
			if (b(p, null, n, q) && n.successFunction) {
				if (n.async) {
					setTimeout(function() {
						n.successFunction(p, q);
					}, 1);
				} else {
					n.successFunction(p, q);
				}
			}
			return;
		}
		var l = p, o = n;
		var u = jQuery(l);
		var v = u.attr("tagName");
		o = (typeof (o) == "boolean" ? o : true);
		switch (v) {
		case "SELECT":
		case "INPUT":
		case "TEXTAREA":
			return e(u);
		}
		var r = u
				.find("select[validator],input[validator],textarea[validator]");
		var t = true;
		for ( var m = 0; m < r.length; m++) {
			if (!e(jQuery(r[m]))) {
				if (o) {
					return false;
				} else {
					t = false;
				}
			}
		}
		return t;
	};
	ol.form.validator.register = function(m, l) {
		i[m] = l;
	};
	ol.form.validator.get = function(l) {
		return i[l];
	};
	var c = 0;
	ol.form.validator.bind = function(m, o) {
		var n = [];
		var r = jQuery(m);
		o = jQuery.extend({}, h, o);
		if (o.validOnChange) {
			r.change(function() {
				ol.form.validator(this, true);
			});
		}
		for ( var p = 0; p < r.length; p++) {
			var q = jQuery(r[p]);
			var l = q.attr("validator");
			if (!l) {
				l = "validator" + (++c) + (new Date()).getTime();
				q.attr("validator", l);
			}
			if (!a[l]) {
				a[l] = [];
			}
			a[l].push(o);
			n.push(l);
		}
		return n;
	};
	ol.form.validator.defaults = h;
	var g = ol.form.validator, f = function(m, l) {
		return !m;
	};
	g.register("regex", function(m, l) {
		if (l.allowEmpty && f(m)) {
			return true;
		}
		if (!l.regex) {
			k("regex", "need parameter of 'regex'!");
			return false;
		}
		return l.regex.test(m);
	});
	g.register("require", function(m, l) {
		l.allowEmpty = false;
		return !f(m);
	});
	g.register("email", function(m, l) {
		if (l.allowEmpty && f(m)) {
			return true;
		}
		var n = null;
		if (l.separater) {
			n = l.separater;
		}
		return ol.util.isEmail(m, n);
	});
	g.register("eq", function(o, l) {
		if (!l.compareTo) {
			k("eq", "need parameter of 'compareTo'!");
			return false;
		}
		var m = jQuery(l.compareTo).val();
		if (!l.format) {
			return o == m;
		}
		var n = g.get("date");
		if (!(n(o, l) && n(m, l))) {
			return false;
		}
		return o.parseDate(l.format).getTime() == m.parseDate(l.format)
				.getTime();
	});
	g.register("lt", function(p, m) {
		if (m.allowEmpty && f(p)) {
			return true;
		}
		if (!m.compareTo) {
			k("lt", "need parameter of 'compareTo'!");
			return false;
		}
		var n = jQuery(m.compareTo).val();
		if (!m.format) {
			m.negative = true;
			var l = g.get("float");
			if (!l(p, m)) {
				return false;
			}
			if (!l(n, m)) {
				return false;
			}
			p = parseFloat(p);
			n = parseFloat(n);
			return p < n;
		}
		var o = g.get("date");
		if (!(o(p, m) && o(n, m))) {
			return false;
		}
		return p.parseDate(m.format) < n.parseDate(m.format);
	});
	g.register("le", function(o, n) {
		var m = g.get("eq");
		var l = g.get("lt");
		return m(o, n) || l(o, n);
	});
	g.register("gt", function(p, m) {
		if (m.allowEmpty && f(p)) {
			return true;
		}
		if (!m.compareTo) {
			k("gt", "need parameter of 'compareTo'!");
			return false;
		}
		var n = jQuery(m.compareTo).val();
		if (!m.format) {
			m.negative = true;
			var l = g.get("float");
			if (!l(p, m)) {
				return false;
			}
			if (!l(n, m)) {
				return false;
			}
			p = parseFloat(p);
			n = parseFloat(n);
			return p > n;
		}
		var o = g.get("date");
		if (!(o(p, m) && o(n, m))) {
			return false;
		}
		return p.parseDate(m.format) > n.parseDate(m.format);
	});
	g.register("ge", function(o, n) {
		var l = g.get("eq");
		var m = g.get("gt");
		return l(o, n) || m(o, n);
	});
	g.register("length", function(n, m) {
		if (m.allowEmpty && f(n)) {
			return true;
		}
		if (!(m.min || m.max)) {
			k("length", "need parameter of 'min' or 'max'!");
			return false;
		}
		var l = n.len();
		if (m.min && l < m.min) {
			return false;
		}
		if (m.max && l > m.max) {
			return false;
		}
		return true;
	});
	g.register("range", function(o, n) {
		if (n.allowEmpty && f(o)) {
			return true;
		}
		if (!(n.min || n.max)) {
			k("range", "need parameter of 'min' or 'max'!");
			return false;
		}
		n.negative = true;
		var m = g.get("float");
		if (!m(o, n)) {
			return false;
		}
		var l = parseFloat(o);
		if (n.min && l < n.min) {
			return false;
		}
		if (n.max && l > n.max) {
			return false;
		}
		return true;
	});
	g.register("chinese", function(m, l) {
		if (l.allowEmpty && f(m)) {
			return true;
		}
		l.regex = /^[\u4E00-\u9FA5]$/;
		return g.get("regex")(m, l);
	});
	g.register("int", function(m, l) {
		if (l.allowEmpty && f(m)) {
			return true;
		}
		if (l.negative) {
			l.regex = /^[-]?\d+$/;
		} else {
			l.regex = /^\d+$/;
		}
		return g.get("regex")(m, l);
	});
	g.register("float", function(m, l) {
		if (l.allowEmpty && f(m)) {
			return true;
		}
		if (l.negative) {
			l.regex = /^[-]?\d+(\.\d+)?$/;
		} else {
			l.regex = /^\d+(\.\d+)?$/;
		}
		return g.get("regex")(m, l);
	});
	g.register("date", function(q, n) {
		if (n.allowEmpty && f(q)) {
			return true;
		}
		if (!n.format) {
			d("date", "need parameter of 'format'!");
			return false;
		}
		var p = {
			"\\." : "\\.",
			"M+" : "(0[1-9]|[1-9]|1[0-2])",
			"d+" : "(0[1-9]|[1-9]|[12][0-9]|3[01])",
			"y+" : "(\\d{4})",
			"H+" : "([0-9]|[01][0-9]|2[0-3])",
			"m+" : "([0-9]|[0-5][0-9])",
			"s+" : "([0-9]|[0-5][0-9])",
			S : "(\\d+)"
		};
		var l = n.format;
		for ( var m in p) {
			if (new RegExp("(" + m + ")").test(n.format)) {
				l = l.replace(RegExp.$1, p[m]);
			}
		}
		n.regex = new RegExp("^" + l + "$");
		if (!g.get("regex")(q, n)) {
			return false;
		}
		return q == q.parseDate(n.format).format(n.format);
	});
	g
			.register(
					"url",
					function(m, l) {
						if (l.allowEmpty && f(m)) {
							return true;
						}
						if (m.startWith("http://")) {
							m = m.substring(7);
						} else {
							if (m.startWith("https://")) {
								m = m.substring(8);
							} else {
								if (l.requireProtocol) {
									return false;
								}
							}
						}
						if (!m) {
							return false;
						}
						l.regex = /^[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/;
						return g.get("regex")(m, l);
					});
})();
(function() {
	var d = ol.template = function(i) {
		this.init = function() {
			i = c(i);
			var k;
			for ( var j = 0; j < i.length; j++) {
				k = f(i[j]);
				g[k[0]] = a(k);
			}
		};
		this.parse = function(j, k) {
			if (!j || !(j = g[j])) {
				return "";
			}
			return e(j, k);
		};
		this.init();
	};
	d.parse = function(j, k) {
		var i = e(a(f(j)), k);
		return i;
	};
	d.startDelimiter = "<!--";
	d.endDelimiter = "-->";
	var g = {}, h = new RegExp(d.startDelimiter + "#macro \\S+\\s*\\w*"
			+ d.endDelimiter + "[\\s\\S]*?" + d.startDelimiter + "/#macro"
			+ d.endDelimiter, "g"), c = function(i) {
		return i.match(h);
	}, b = new RegExp("(" + d.startDelimiter + "(/?)#([\\s\\S]*?)"
			+ d.endDelimiter + ")|(')|([\r\n\t])|({#([^}]*?)})", "g"), f = function(
			s) {
		var r = s.replace(b, function(C, B, A, z, y, x, t, i) {
			if (B) {
				return "\n" + (A ? "-" : "+") + z.replace(/[\r\n\t]/g, "")
						+ "\n";
			}
			if (y) {
				return "\\'";
			}
			if (x) {
				return "";
			}
			if (t) {
				var w = i.indexOf("?");
				if (w != 0) {
					switch (i.substring(w + 1)) {
					case "html":
						i = "ol.util.escapeHtml(" + i.substring(0, w) + ")";
						break;
					case "js_string":
						i = i.replace(/\"/g, '\\"').replace(/\r\n/g, "\\r\\n")
								.replace(/\n/g, "\\n");
						break;
					}
				}
				i = i.replace(/\'/g, "\\'");
				return "'+(" + i + ")+'";
			}
		});
		r = r.split(/\n/);
		var u, m, k, v, j, n = [ "var f=[];" ];
		for ( var l = 0; l < r.length; l++) {
			u = r[l];
			if (!u) {
				continue;
			}
			m = u.charAt(0);
			if (m !== "+" && m !== "-") {
				n.push("f.push('" + u + "');");
				continue;
			}
			k = u.split(/\s/);
			switch (k[0]) {
			case "+macro":
				v = k[1];
				j = k[2];
				n.push('f.push("<!--' + v + ' start-->");');
				break;
			case "-macro":
				n.push('f.push("<!--' + v + ' end-->");');
				break;
			case "+elseif":
				k.splice(0, 1);
				n.push("}else if" + k.join(" ") + "{");
				break;
			case "+else":
				n.push("}else{");
				break;
			case "+if":
			case "+for":
			case "+switch":
				k[0] = k[0].substr(1);
				n.push(k.join(" ") + "{");
				break;
			case "+case":
			case "+default":
				k[0] = k[0].substr(1);
				n.push(k.join(" ") + ":");
				break;
			case "-switch":
			case "-for":
			case "-if":
				n.push("}");
				break;
			case "+list":
				if (k.length != 4) {
					throw v + ": list command error!";
				}
				var q = k[3] + "_index", o = k[3] + "_length", p = k[3]
						+ "_num";
				n.push("if(" + k[1] + ".constructor === Array){");
				n.push("var " + k[3] + ";");
				n.push("var " + o + "=" + k[1] + ".length;");
				n.push("var " + q + ";");
				n.push("for(var " + p + "=" + o + ";" + p + "--;){");
				n.push(q + "=" + o + "-" + p + "-1;");
				n.push(k[3] + "=" + k[1] + "[" + q + "];");
				break;
			case "-list":
				n.push("}}");
				break;
			case "+break":
				n.push("break;");
				break;
			case "+eval":
				k.splice(0, 1);
				n.push(k.join(" "));
				break;
			case "+var":
				k[0] = k[0].substr(1);
				n.push(k.join(" ") + ";");
				break;
			default:
				break;
			}
		}
		n.push("return f.join('');");
		return [ v, j || "data", n.join("") ];
	}, a = function(i) {
		try {
			return new Function(i[1], i[2]);
		} catch (j) {
			logger.error("template:" + i[0], j);
		}
	}, e = function(i, j) {
		return i(j);
	};
})();
