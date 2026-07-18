/**
 * 定义命名空间ol
 */
if (!window.ol) {
	window.ol = {
		debug : true,
		isIE :
		/* @cc_on!@ */
		0,
		isIE6 : false,
		isIE7 : false,
		isIE8 : false,
		domCompleted : false
	}
};

/**
 * 定位到i或window下通过h路径查找的对象（不存在则创建一个空对象），将g赋给这个对象
 * @param i scope
 * @param h 路径
 * @param g	赋值对象
 * @returns
 */
ol.pkg = function(i, h, g) {
	var d, e, b;
	if (arguments.length == 3) {
		d = i;
		e = h;
		b = g
	} else {
		d = window;
		e = i;
		b = h
	}
	if (!e || !e.length) {
		return null
	}
	var l = e.split(".");
	for ( var k = d, j = 0; j < l.length - 1; j++) {
		k[l[j]] || (k[l[j]] = {});
		k = k[l[j]]
	}
	k = (k[l[l.length - 1]] = b || k[l[l.length - 1]] || {});
	return k
};

/**
 * dom加载
 */
(function() {
	var c = 0, 
	e = [], 
	/**
	 * 执行缓存在数组e中的函数（当调用ec.ready(f)时，函数f依次缓存在e中）
	 */
	d = function() {
		for ( var f = 0; f < e.length; f++) {
			e[f].call(document)
		}
		e.length = 0
	}, 
	/**
	 * 检查dom是否加载完成，加载完成后依次执行缓存在e中的函数
	 */
	a = function() {
		if (ol.domCompleted) {
			return
		}
		if (!document.body) {
			setTimeout(arguments.callee, 13);
			return
		}
		ol.domCompleted = true;
		d()
	}, 
	/**
	 * 绑定dom加载处理程序
	 */
	b = {
		DOMContentLoaded : function() {
			if (document.addEventListener) {
				document.removeEventListener("DOMContentLoaded",
						b.DOMContentLoaded, false);
				a()
			} else {
				if (document.attachEvent) {
					if (document.readyState === "complete") {
						document.detachEvent("onreadystatechange",
								b.DOMContentLoaded);
						a()
					}
				}
			}
		},
		bindReady : function() {
			var f = b.DOMContentLoaded;
			if (document.readyState === "complete") {
				a();
				return
			}
			if (document.addEventListener) {
				document.addEventListener("DOMContentLoaded", f, false);
				window.addEventListener("load", a, false)
			} else {
				if (document.attachEvent) {
					document.attachEvent("onreadystatechange", f);
					window.attachEvent("onload", a);
					var g = false;
					try {
						g = window.frameElement == null
					} catch (h) {
					}
					if (document.documentElement.doScroll && g) {
						b.doScrollCheck()
					}
				}
			}
		},
		/**
		 * 利用doScroll方法检查dom是否加载完成
		 */
		doScrollCheck : function() {
			if (ol.domCompleted) {
				return
			}
			try {
				document.documentElement.doScroll("left")
			} catch (f) {
				setTimeout(b.doScrollCheck, 1);
				return
			}
			a()
		}
	};
	/**
	 * 等待dom加载完成后执行函数f
	 */
	ol.ready = function(f) {
		if (typeof (f) != "function") {
			return
		}
		if (ol.domCompleted) {
			f.call(document);
			return
		} else {
			if (e.length == 0) {
				b.bindReady()
			}
		}
		e.push(f)
	}
})();

/**
 * js加载
 */
(function() {
	var h = document.head || document.getElementsByTagName("head")[0]
			|| document.documentElement, 
		a = 0,
		e = {}, 
		b = {}, 
		/**
		 * 绑定加载完成事件，加载完成后移除节点
		 */
		g = function(j,k) {
			j.onload = j.onerror = j.onreadystatechange = function() {
				if (/loaded|complete|undefined/.test(j.readyState)) {
					j.onload = j.onerror = j.onreadystatechange = null;
					if (j.parentNode) {
						try {
							if (j.clearAttributes) {
								j.clearAttributes()
							} else {
								for ( var m in j) {
									delete j[m]
								}
							}
						} catch (l) {
						};
						h.removeChild(j);
					}
					j = undefined;
					k()
				}
			}
		}, 
		
		f = function(l, o) {
			var j = [];
			var n = "jsapi_loader" + (a++);
			j.push("(function(){var time = 0,el = document.getElementById('"
					+ n
						+ "');if(!el || (el.readyState && 'complete' != el.readyState)){ if(time<10){ setTimeout(arguments.callee,30); time++; }else{ logger.error('load the script of id "
						+ n + " fail!');} return; }");
			if (l.onload) {
				j.push("(");
				j.push(l.onload);
				j.push(")();")
			}
			if ("object" == typeof (o)) {
				for ( var k = 0; k < o.length; k++) {
					if ("function" == typeof (o[k])) {
						j.push("(");
						j.push(o[k]);
						j.push(")();")
					} else {
						if ("string" == typeof (o[k])) {
							j.push(o[k])
						}
					}
				}
			}
			var m = "";
			if (l.charset) {
				m = 'charset="' + l.charset + '"'
			}
			j.push("})();");
			document.write('<script id="' + n
				+ '" loadType="insert" type="text/javascript" src="' + l.url
				+ '" ' + m + "><\/script>");
			document.write("<script>" + j.join("") + "<\/script>")
		}, 
		/**
		 * 生成script节点插入页面开始加载文件
		 */
		d = function(j, m) {
			var l = document.createElement("script");
			l.src = j.url;
			l.async = !j.depend;
			l.type = "text/javascript";
			if (j.charset) {
				l.charset = j.charset
			}
			var k = l;
			h.insertBefore(l, h.firstChild);
			k = null;
			g(l, m)
		}, 
		i = function(j) {
			var k = document.createElement("link");
			k.rel = "stylesheet";
			k.type = "text/css";
			k.href = j.url;
			h.appendChild(k)
		}, 
	/**
	 * 加载js或css
	 */
	c = function(l, k) {
		var j = this;
		this._insertMark = {};
		this._css_queue = [];
		this._js_queue = [];
		this._wait_map = {};
		//2将要加载的项存入相应数组中
		this._push = function(m) {
			if (!m.mark) {
				m.mark = (m.uri || m.url)
			}
			if (this._insertMark[m.mark]) {
				return
			}
			this._insertMark[m.mark] = true;
			("js" == m.type ? this._js_queue : this._css_queue).push(m)
		};
		//1解析要加载的js或css对象，对象信息存在e中
		this._attach = function(o) {
			if ("string" == typeof (o)) {
				var m = o;
				o = e[m];
				if (!o) {
					if (ol.debug) {
						logger.warn("ol.load", m + " is undefined!")
					}
					return
				}
				this._attach(o);
				return
			} else {
				if ("[object Array]" == Object.prototype.toString.apply(o)) {
					for ( var n = 0; n < o.length; n++) {
						this._attach(o[n])
					}
					return
				}
			}
			this._push(o)
		};
		//3.加载js或css
		this._load = function(p) {
			(p.type == "js" ? this._js_queue : this._css_queue).splice(0, 1);
			var o = this._js_queue.length > 0 && this._js_queue[0].depend;
			var n = b[p.mark];
			if (n) {
				switch (n.status) {
				case "complete":
					if (p.onload) {
						setTimeout(p.onload, 50)
					}
					return true;
				case "active":
					if (!ol.domCompleted) {
						return true
					}
					var r = 1;
					var q = function() {
						var m = p;
						if (b[m.mark].status == "complete") {
							delete j._wait_map[m.mark];
							if (m.onload) {
								m.onload.call(document)
							}
							if (!o) {
								j.success();
								return
							}
							ol.load(j._js_queue, k);
							return
						}
						r++;
						if (r <= 20) {
							setTimeout(q, 50)
						}
					};
					setTimeout(q, 50);
					this._wait_map[p.mark] = true;
					return !o
				}
			}
			if (ol.domCompleted) {
				p.loadType = null
			}
			if (p.loadType == "lazy") {
				ol.ready(function() {
					j._load(p)
				});
				return true
			}
			if (!p.url) {
				if (p.uri.substr(0, 7) == "http://"
						|| p.uri.substr(0, 8) == "https://") {
					p.url = p.uri
				} else {
					p.url = ol.libPath + p.uri
				}
			}
			if (p.type == "js") {
				b[p.mark] = {
					status : "active"
				};
				if (!ol.domCompleted) {
					var s = [];
					s.push('ol._setLoadStatus("' + p.mark + '","complete");');
					if (!o) {
						s.push(k.callback)
					}
					f(p, s)
				} else {
					var s = function() {
						b[p.mark] = {
							status : "complete"
						};
						if (p.onload) {
							p.onload.call(document)
						}
						if (o) {
							ol.load(j._js_queue, k)
						} else {
							j.success()
						}
					};
					d(p, s);
					return !o
				}
			} else {
				i(p)
			}
			return true
		};
		this.success = function() {
			if (this._js_queue.length > 0) {
				return
			}
			for ( var m in this._wait_map) {
				return
			}
			if (k.onload) {
				k.onload.call(document)
			}
			if (k.callback) {
				ol.ready(k.callback)
			}
		};
		this.init = function() {
			if ("[object object]" == Object.prototype.toString.apply(l)) {
				this._load(l);
				return
			} else {
				this._attach(l);
				this._insertMark = undefined;
				while (this._css_queue.length > 0) {
					this._load(this._css_queue[0])
				}
				while (this._js_queue.length > 0 && this._load(this._js_queue[0])) {
				}
			}
		};
		this.init()
	};
	//保存文件类型，路径等信息
	ol.define = function(j, k) {
		e[j] = k
	};
	//移除信息
	ol.remove = function(j) {
		delete e[j]
	};
	//加载文件
	ol.load = function(k, j) {
		if ("function" == typeof (j)) {
			j = {
				callback : j
			}
		} else {
			if (!j) {
				j = {}
			}
		}
		if (!ol.domCompleted && j.loadType == "lazy") {
			ol.ready(function() {
				new c(k, j)
			})
		} else {
			new c(k, j)
		}
	};
	//设置文件加载状态：complete,active
	ol._setLoadStatus = function(k, j) {
		b[k] = {
			status : j
		}
	}
})();

/**
 * modify by cj 20121031
 * 输出调试信息
 */
var logger = {
	info : function(b, a) {
		if (!ol.debug) {
			return
		}
		if (typeof (console) != "undefined" && console.log) {
			if (a) {
				console.log("[" + b + "]:" + a)
			} else {
				console.log(b)
			}
		}
	},
	warn : function(b, a) {
		if (!ol.debug) {
			return
		}
		if (typeof (console) != "undefined" && console.warn) {
			if (a) {
				console.warn("[" + b + "]:" + a)
			} else {
				console.warn(b)
			}
		}
	},
	error : function(b, a) {
		if (!ol.debug) {
			return
		}
		if (typeof (console) != "undefined" && console.error) {
			if (a) {
				console.error("[" + b + "]:" + a)
			} else {
				console.error(b)
			}
		}
	}
};
function log(b, a) {
	logger.info(b, a)
}

/**
 * modify by cj 20121031
 * 将命名空间ol变为ec:window.ec = ol;
 * 保存路径libPath="http://localhost:8080/oauth2/rss_1/default/portal_rss/js/common/"
*/
(function() {
	var a = document.getElementsByTagName("script"), b = a[a.length - 1];
	var f = b.src.lastIndexOf("/");
	if (f > 0) {
		ol.libPath = b.src.substring(0, f + 1)
	}
	var c = b.getAttribute("namespace");
	if (c) {
		window[c] = ol
	}
	b = null;
	try {
		document.write("<!--[if lte IE 6]><script>ol.isIE6=true;<\/script><![endif]--><!--[if IE 7]><script>ol.isIE7=true;<\/script><![endif]--><!--[if IE 8]><script>ol.isIE8=true;<\/script><![endif]-->")
	} catch (d) {
	}
})();

/**
 * dom加载完成
 */
ol.ready(function() {
	ol.domCompleted = true;
	log("Dom", "Load Complete!")
});

