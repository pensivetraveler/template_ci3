/*! sly 1.6.0 - 17th Jul 2015 | https://github.com/darsain/sly */ ! function(a, b, c) {
    "use strict";

    function d(b, p, q) {
        function K(c) {
            var d = 0,
                e = Gb.length;
            if (yb.old = a.extend({}, yb), wb = tb ? 0 : ub[rb.horizontal ? "width" : "height"](), Bb = zb[rb.horizontal ? "width" : "height"](), xb = tb ? b : vb[rb.horizontal ? "outerWidth" : "outerHeight"](), Gb.length = 0, yb.start = 0, yb.end = H(xb - wb, 0), Rb) {
                d = Ib.length, Hb = vb.children(rb.itemSelector), Ib.length = 0;
                var f, g = j(vb, rb.horizontal ? "paddingLeft" : "paddingTop"),
                    h = j(vb, rb.horizontal ? "paddingRight" : "paddingBottom"),
                    i = "border-box" === a(Hb).css("boxSizing"),
                    l = "none" !== Hb.css("float"),
                    m = 0,
                    n = Hb.length - 1;
                xb = 0, Hb.each(function(b, c) {
                    var d = a(c),
                        e = c.getBoundingClientRect(),
                        i = G(rb.horizontal ? e.width || e.right - e.left : e.height || e.bottom - e.top),
                        k = j(d, rb.horizontal ? "marginLeft" : "marginTop"),
                        o = j(d, rb.horizontal ? "marginRight" : "marginBottom"),
                        p = i + k + o,
                        q = !k || !o,
                        r = {};
                    r.el = c, r.size = q ? i : p, r.half = r.size / 2, r.start = xb + (q ? k : 0), r.center = r.start - G(wb / 2 - r.size / 2), r.end = r.start - wb + r.size, b || (xb += g), xb += p, rb.horizontal || l || o && k && b > 0 && (xb -= I(k, o)), b === n && (r.end += h, xb += h, m = q ? o : 0), Ib.push(r), f = r
                }), vb[0].style[rb.horizontal ? "width" : "height"] = (i ? xb : xb - g - h) + 10 + "px", xb -= m, Ib.length ? (yb.start = Ib[0][Pb ? "center" : "start"], yb.end = Pb ? f.center : xb > wb ? f.end : yb.start) : yb.start = yb.end = 0
				// 10px 추가
			}
            if (yb.center = G(yb.end / 2 + yb.start / 2), V(), Ab.length && Bb > 0 && (rb.dynamicHandle ? (Cb = yb.start === yb.end ? Bb : G(Bb * wb / xb), Cb = k(Cb, rb.minHandleSize, Bb), Ab[0].style[rb.horizontal ? "width" : "height"] = Cb + "px") : Cb = Ab[rb.horizontal ? "outerWidth" : "outerHeight"](), Db.end = Bb - Cb, ec || N()), !tb && wb > 0) {
                var o = yb.start,
                    p = "";
                if (Rb) a.each(Ib, function(a, b) {
                    Pb ? Gb.push(b.center) : b.start + b.size > o && o <= yb.end && (o = b.start, Gb.push(o), o += wb, o > yb.end && o < yb.end + wb && Gb.push(yb.end))
                });
                else
                    for (; o - wb < yb.end;) Gb.push(o), o += wb;
                if (Eb[0] && e !== Gb.length) {
                    for (var q = 0; q < Gb.length; q++) p += rb.pageBuilder.call(sb, q);
                    Fb = Eb.html(p).children(), Fb.eq(Jb.activePage).addClass(rb.activeClass)
                }
            }
            if (Jb.slideeSize = xb, Jb.frameSize = wb, Jb.sbSize = Bb, Jb.handleSize = Cb, Rb) {
                c && null != rb.startAt && (T(rb.startAt), sb[Qb ? "toCenter" : "toStart"](rb.startAt));
                var r = Ib[Jb.activeItem];
                L(Qb && r ? r.center : k(yb.dest, yb.start, yb.end))
            } else c ? null != rb.startAt && L(rb.startAt, 1) : L(k(yb.dest, yb.start, yb.end));
            ob("load")
        }

        function L(a, b, c) {
            if (Rb && cc.released && !c) {
                var d = U(a),
                    e = a > yb.start && a < yb.end;
                Qb ? (e && (a = Ib[d.centerItem].center), Pb && rb.activateMiddle && T(d.centerItem)) : e && (a = Ib[d.firstItem].start)
            }
            cc.init && cc.slidee && rb.elasticBounds ? a > yb.end ? a = yb.end + (a - yb.end) / 6 : a < yb.start && (a = yb.start + (a - yb.start) / 6) : a = k(a, yb.start, yb.end), ac.start = +new Date, ac.time = 0, ac.from = yb.cur, ac.to = a, ac.delta = a - yb.cur, ac.tweesing = cc.tweese || cc.init && !cc.slidee, ac.immediate = !ac.tweesing && (b || cc.init && cc.slidee || !rb.speed), cc.tweese = 0, a !== yb.dest && (yb.dest = a, ob("change"), ec || M()), Z(), V(), W(), O()
        }

        function M() {
            if (sb.initialized) {
                if (!ec) return ec = t(M), void(cc.released && ob("moveStart"));
                ac.immediate ? yb.cur = ac.to : ac.tweesing ? (ac.tweeseDelta = ac.to - yb.cur, D(ac.tweeseDelta) < .1 ? yb.cur = ac.to : yb.cur += ac.tweeseDelta * (cc.released ? rb.swingSpeed : rb.syncSpeed)) : (ac.time = I(+new Date - ac.start, rb.speed), yb.cur = ac.from + ac.delta * a.easing[rb.easing](ac.time / rb.speed, ac.time, 0, 1, rb.speed)), ac.to === yb.cur ? (yb.cur = ac.to, cc.tweese = ec = 0) : ec = t(M), ob("move"), tb || (m ? vb[0].style[m] = n + (rb.horizontal ? "translateX" : "translateY") + "(" + -yb.cur + "px)" : vb[0].style[rb.horizontal ? "left" : "top"] = -G(yb.cur) + "px"), !ec && cc.released && ob("moveEnd"), N()
            }
        }

        function N() {
            Ab.length && (Db.cur = yb.start === yb.end ? 0 : ((cc.init && !cc.slidee ? yb.dest : yb.cur) - yb.start) / (yb.end - yb.start) * Db.end, Db.cur = k(G(Db.cur), Db.start, Db.end), _b.hPos !== Db.cur && (_b.hPos = Db.cur, m ? Ab[0].style[m] = n + (rb.horizontal ? "translateX" : "translateY") + "(" + Db.cur + "px)" : Ab[0].style[rb.horizontal ? "left" : "top"] = Db.cur + "px"))
        }

        function O() {
            Fb[0] && _b.page !== Jb.activePage && (_b.page = Jb.activePage, Fb.removeClass(rb.activeClass).eq(Jb.activePage).addClass(rb.activeClass), ob("activePage", _b.page))
        }

        function P() {
            bc.speed && yb.cur !== (bc.speed > 0 ? yb.end : yb.start) || sb.stop(), hc = cc.init ? t(P) : 0, bc.now = +new Date, bc.pos = yb.cur + (bc.now - bc.lastTime) / 1e3 * bc.speed, L(cc.init ? bc.pos : G(bc.pos)), cc.init || yb.cur !== yb.dest || ob("moveEnd"), bc.lastTime = bc.now
        }

        function Q(a, b, d) {
            if ("boolean" === e(b) && (d = b, b = c), b === c) L(yb[a], d);
            else {
                if (Qb && "center" !== a) return;
                var f = sb.getPos(b);
                f && L(f[a], d, !Qb)
            }
        }

        function R(a) {
            return null != a ? i(a) ? a >= 0 && a < Ib.length ? a : -1 : Hb.index(a) : -1
        }

        function S(a) {
            return R(i(a) && 0 > a ? a + Ib.length : a)
        }

        function T(a, b) {
            var c = R(a);
            return !Rb || 0 > c ? !1 : ((_b.active !== c || b) && (Hb.eq(Jb.activeItem).removeClass(rb.activeClass), Hb.eq(c).addClass(rb.activeClass), _b.active = Jb.activeItem = c, W(), ob("active", c)), c)
        }

        function U(a) {
            a = k(i(a) ? a : yb.dest, yb.start, yb.end);
            var b = {},
                c = Pb ? 0 : wb / 2;
            if (!tb)
                for (var d = 0, e = Gb.length; e > d; d++) {
                    if (a >= yb.end || d === Gb.length - 1) {
                        b.activePage = Gb.length - 1;
                        break
                    }
                    if (a <= Gb[d] + c) {
                        b.activePage = d;
                        break
                    }
                }
            if (Rb) {
                for (var f = !1, g = !1, h = !1, j = 0, l = Ib.length; l > j; j++)
                    if (f === !1 && a <= Ib[j].start + Ib[j].half && (f = j), h === !1 && a <= Ib[j].center + Ib[j].half && (h = j), j === l - 1 || a <= Ib[j].end + Ib[j].half) {
                        g = j;
                        break
                    }
                b.firstItem = i(f) ? f : 0, b.centerItem = i(h) ? h : b.firstItem, b.lastItem = i(g) ? g : b.centerItem
            }
            return b
        }

        function V(b) {
            a.extend(Jb, U(b))
        }

        function W() {
            var a = yb.dest <= yb.start,
                b = yb.dest >= yb.end,
                c = (a ? 1 : 0) | (b ? 2 : 0);
            if (_b.slideePosState !== c && (_b.slideePosState = c, Yb.is("button,input") && Yb.prop("disabled", a), Zb.is("button,input") && Zb.prop("disabled", b), Yb.add(Vb)[a ? "addClass" : "removeClass"](rb.disabledClass), Zb.add(Ub)[b ? "addClass" : "removeClass"](rb.disabledClass)), _b.fwdbwdState !== c && cc.released && (_b.fwdbwdState = c, Vb.is("button,input") && Vb.prop("disabled", a), Ub.is("button,input") && Ub.prop("disabled", b)), Rb && null != Jb.activeItem) {
                var d = 0 === Jb.activeItem,
                    e = Jb.activeItem >= Ib.length - 1,
                    f = (d ? 1 : 0) | (e ? 2 : 0);
                _b.itemsButtonState !== f && (_b.itemsButtonState = f, Wb.is("button,input") && Wb.prop("disabled", d), Xb.is("button,input") && Xb.prop("disabled", e), Wb[d ? "addClass" : "removeClass"](rb.disabledClass), Xb[e ? "addClass" : "removeClass"](rb.disabledClass))
            }
        }

        function X(a, b, c) {
            if (a = S(a), b = S(b), a > -1 && b > -1 && a !== b && (!c || b !== a - 1) && (c || b !== a + 1)) {
                Hb.eq(a)[c ? "insertAfter" : "insertBefore"](Ib[b].el);
                var d = b > a ? a : c ? b : b - 1,
                    e = a > b ? a : c ? b + 1 : b,
                    f = a > b;
                null != Jb.activeItem && (a === Jb.activeItem ? _b.active = Jb.activeItem = c ? f ? b + 1 : b : f ? b : b - 1 : Jb.activeItem > d && Jb.activeItem < e && (_b.active = Jb.activeItem += f ? 1 : -1)), K()
            }
        }

        function Y(a, b) {
            for (var c = 0, d = $b[a].length; d > c; c++)
                if ($b[a][c] === b) return c;
            return -1
        }

        function Z() {
            cc.released && !sb.isPaused && sb.resume()
        }

        function $(a) {
            return G(k(a, Db.start, Db.end) / Db.end * (yb.end - yb.start)) + yb.start
        }

        function _() {
            cc.history[0] = cc.history[1], cc.history[1] = cc.history[2], cc.history[2] = cc.history[3], cc.history[3] = cc.delta
        }

        function ab(a) {
            cc.released = 0, cc.source = a, cc.slidee = "slidee" === a
        }

        function bb(b) {
            var c = "touchstart" === b.type,
                d = b.data.source,
                e = "slidee" === d;
            cc.init || !c && eb(b.target) || ("handle" !== d || rb.dragHandle && Db.start !== Db.end) && (!e || (c ? rb.touchDragging : rb.mouseDragging && b.which < 2)) && (c || f(b), ab(d), cc.init = 0, cc.$source = a(b.target), cc.touch = c, cc.pointer = c ? b.originalEvent.touches[0] : b, cc.initX = cc.pointer.pageX, cc.initY = cc.pointer.pageY, cc.initPos = e ? yb.cur : Db.cur, cc.start = +new Date, cc.time = 0, cc.path = 0, cc.delta = 0, cc.locked = 0, cc.history = [0, 0, 0, 0], cc.pathToLock = e ? c ? 30 : 10 : 0, u.on(c ? x : w, cb), sb.pause(1), (e ? vb : Ab).addClass(rb.draggedClass), ob("moveStart"), e && (fc = setInterval(_, 10)))
        }

        function cb(a) {
            if (cc.released = "mouseup" === a.type || "touchend" === a.type, cc.pointer = cc.touch ? a.originalEvent[cc.released ? "changedTouches" : "touches"][0] : a, cc.pathX = cc.pointer.pageX - cc.initX, cc.pathY = cc.pointer.pageY - cc.initY, cc.path = E(F(cc.pathX, 2) + F(cc.pathY, 2)), cc.delta = rb.horizontal ? cc.pathX : cc.pathY, cc.released || !(cc.path < 1)) {
                if (!cc.init) {
                    if (cc.path < rb.dragThreshold) return cc.released ? db() : c;
                    if (!(rb.horizontal ? D(cc.pathX) > D(cc.pathY) : D(cc.pathX) < D(cc.pathY))) return db();
                    cc.init = 1
                }
                f(a), !cc.locked && cc.path > cc.pathToLock && cc.slidee && (cc.locked = 1, cc.$source.on(z, g)), cc.released && (db(), rb.releaseSwing && cc.slidee && (cc.swing = (cc.delta - cc.history[0]) / 40 * 300, cc.delta += cc.swing, cc.tweese = D(cc.swing) > 10)), L(cc.slidee ? G(cc.initPos - cc.delta) : $(cc.initPos + cc.delta))
            }
        }

        function db() {
            clearInterval(fc), cc.released = !0, u.off(cc.touch ? x : w, cb), (cc.slidee ? vb : Ab).removeClass(rb.draggedClass), setTimeout(function() {
                cc.$source.off(z, g)
            }), yb.cur === yb.dest && cc.init && ob("moveEnd"), sb.resume(1), cc.init = 0
        }

        function eb(b) {
            return ~a.inArray(b.nodeName, B) || a(b).is(rb.interactive)
        }

        function fb() {
            sb.stop(), u.off("mouseup", fb)
        }

        function gb(a) {
            switch (f(a), this) {
                case Ub[0]:
                case Vb[0]:
                    sb.moveBy(Ub.is(this) ? rb.moveBy : -rb.moveBy), u.on("mouseup", fb);
                    break;
                case Wb[0]:
                    sb.prev();
                    break;
                case Xb[0]:
                    sb.next();
                    break;
                case Yb[0]:
                    sb.prevPage();
                    break;
                case Zb[0]:
                    sb.nextPage()
            }
        }

        function hb(a) {
            return dc.curDelta = (rb.horizontal ? a.deltaY || a.deltaX : a.deltaY) || -a.wheelDelta, dc.curDelta /= 1 === a.deltaMode ? 3 : 100, Rb ? (o = +new Date, dc.last < o - dc.resetTime && (dc.delta = 0), dc.last = o, dc.delta += dc.curDelta, D(dc.delta) < 1 ? dc.finalDelta = 0 : (dc.finalDelta = G(dc.delta / 1), dc.delta %= 1), dc.finalDelta) : dc.curDelta
        }

        function ib(a) {
            a.originalEvent[r] = sb;
            var b = +new Date;
            if (J + rb.scrollHijack > b && Sb[0] !== document && Sb[0] !== window) return void(J = b);
            if (rb.scrollBy && yb.start !== yb.end) {
                var c = hb(a.originalEvent);
                (rb.scrollTrap || c > 0 && yb.dest < yb.end || 0 > c && yb.dest > yb.start) && f(a, 1), sb.slideBy(rb.scrollBy * c)
            }
        }

        function jb(a) {
            rb.clickBar && a.target === zb[0] && (f(a), L($((rb.horizontal ? a.pageX - zb.offset().left : a.pageY - zb.offset().top) - Cb / 2)))
        }

        function kb(a) {
            if (rb.keyboardNavBy) switch (a.which) {
                case rb.horizontal ? 37:
                    38: f(a), sb["pages" === rb.keyboardNavBy ? "prevPage" : "prev"]();
                    break;
                case rb.horizontal ? 39:
                    40: f(a), sb["pages" === rb.keyboardNavBy ? "nextPage" : "next"]()
            }
        }

        function lb(a) {
            return eb(this) ? void(a.originalEvent[r + "ignore"] = !0) : void(this.parentNode !== vb[0] || a.originalEvent[r + "ignore"] || sb.activate(this))
        }

        function mb() {
            this.parentNode === Eb[0] && sb.activatePage(Fb.index(this))
        }

        function nb(a) {
            rb.pauseOnHover && sb["mouseenter" === a.type ? "pause" : "resume"](2)
        }

        function ob(a, b) {
            if ($b[a]) {
                for (qb = $b[a].length, C.length = 0, pb = 0; qb > pb; pb++) C.push($b[a][pb]);
                for (pb = 0; qb > pb; pb++) C[pb].call(sb, a, b)
            }
        }
        var pb, qb, rb = a.extend({}, d.defaults, p),
            sb = this,
            tb = i(b),
            ub = a(b),
            vb = rb.slidee ? a(rb.slidee).eq(0) : ub.children().eq(0),
            wb = 0,
            xb = 0,
            yb = {
                start: 0,
                center: 0,
                end: 0,
                cur: 0,
                dest: 0
            },
            zb = a(rb.scrollBar).eq(0),
            Ab = zb.children().eq(0),
            Bb = 0,
            Cb = 0,
            Db = {
                start: 0,
                end: 0,
                cur: 0
            },
            Eb = a(rb.pagesBar),
            Fb = 0,
            Gb = [],
            Hb = 0,
            Ib = [],
            Jb = {
                firstItem: 0,
                lastItem: 0,
                centerItem: 0,
                activeItem: null,
                activePage: 0
            },
            Kb = new l(ub[0]),
            Lb = new l(vb[0]),
            Mb = new l(zb[0]),
            Nb = new l(Ab[0]),
            Ob = "basic" === rb.itemNav,
            Pb = "forceCentered" === rb.itemNav,
            Qb = "centered" === rb.itemNav || Pb,
            Rb = !tb && (Ob || Qb || Pb),
            Sb = rb.scrollSource ? a(rb.scrollSource) : ub,
            Tb = rb.dragSource ? a(rb.dragSource) : ub,
            Ub = a(rb.forward),
            Vb = a(rb.backward),
            Wb = a(rb.prev),
            Xb = a(rb.next),
            Yb = a(rb.prevPage),
            Zb = a(rb.nextPage),
            $b = {},
            _b = {},
            ac = {},
            bc = {},
            cc = {
                released: 1
            },
            dc = {
                last: 0,
                delta: 0,
                resetTime: 200
            },
            ec = 0,
            fc = 0,
            gc = 0,
            hc = 0;
        tb || (b = ub[0]), sb.initialized = 0, sb.frame = b, sb.slidee = vb[0], sb.pos = yb, sb.rel = Jb, sb.items = Ib, sb.pages = Gb, sb.isPaused = 0, sb.options = rb, sb.dragging = cc, sb.reload = function() {
            K()
        }, sb.getPos = function(a) {
            if (Rb) {
                var b = R(a);
                return -1 !== b ? Ib[b] : !1
            }
            var c = vb.find(a).eq(0);
            if (c[0]) {
                var d = rb.horizontal ? c.offset().left - vb.offset().left : c.offset().top - vb.offset().top,
                    e = c[rb.horizontal ? "outerWidth" : "outerHeight"]();
                return {
                    start: d,
                    center: d - wb / 2 + e / 2,
                    end: d - wb + e,
                    size: e
                }
            }
            return !1
        }, sb.moveBy = function(a) {
            bc.speed = a, !cc.init && bc.speed && yb.cur !== (bc.speed > 0 ? yb.end : yb.start) && (bc.lastTime = +new Date, bc.startPos = yb.cur, ab("button"), cc.init = 1, ob("moveStart"), s(hc), P())
        }, sb.stop = function() {
            "button" === cc.source && (cc.init = 0, cc.released = 1)
        }, sb.prev = function() {
            sb.activate(null == Jb.activeItem ? 0 : Jb.activeItem - 1)
        }, sb.next = function() {
            sb.activate(null == Jb.activeItem ? 0 : Jb.activeItem + 1)
        }, sb.prevPage = function() {
            sb.activatePage(Jb.activePage - 1)
        }, sb.nextPage = function() {
            sb.activatePage(Jb.activePage + 1)
        }, sb.slideBy = function(a, b) {
            a && (Rb ? sb[Qb ? "toCenter" : "toStart"](k((Qb ? Jb.centerItem : Jb.firstItem) + rb.scrollBy * a, 0, Ib.length)) : L(yb.dest + a, b))
        }, sb.slideTo = function(a, b) {
            L(a, b)
        }, sb.toStart = function(a, b) {
            Q("start", a, b)
        }, sb.toEnd = function(a, b) {
            Q("end", a, b)
        }, sb.toCenter = function(a, b) {
            Q("center", a, b)
        }, sb.getIndex = R, sb.activate = function(a, b) {
            var c = T(a);
            rb.smart && c !== !1 && (Qb ? sb.toCenter(c, b) : c >= Jb.lastItem ? sb.toStart(c, b) : c <= Jb.firstItem ? sb.toEnd(c, b) : Z())
        }, sb.activatePage = function(a, b) {
            i(a) && L(Gb[k(a, 0, Gb.length - 1)], b)
        }, sb.resume = function(a) {
            rb.cycleBy && rb.cycleInterval && ("items" !== rb.cycleBy || Ib[0] && null != Jb.activeItem) && !(a < sb.isPaused) && (sb.isPaused = 0, gc ? gc = clearTimeout(gc) : ob("resume"), gc = setTimeout(function() {
                switch (ob("cycle"), rb.cycleBy) {
                    case "items":
                        sb.activate(Jb.activeItem >= Ib.length - 1 ? 0 : Jb.activeItem + 1);
                        break;
                    case "pages":
                        sb.activatePage(Jb.activePage >= Gb.length - 1 ? 0 : Jb.activePage + 1)
                }
            }, rb.cycleInterval))
        }, sb.pause = function(a) {
            a < sb.isPaused || (sb.isPaused = a || 100, gc && (gc = clearTimeout(gc), ob("pause")))
        }, sb.toggle = function() {
            sb[gc ? "pause" : "resume"]()
        }, sb.set = function(b, c) {
            a.isPlainObject(b) ? a.extend(rb, b) : rb.hasOwnProperty(b) && (rb[b] = c)
        }, sb.add = function(b, c) {
            var d = a(b);
            Rb ? (null == c || !Ib[0] || c >= Ib.length ? d.appendTo(vb) : Ib.length && d.insertBefore(Ib[c].el), null != Jb.activeItem && c <= Jb.activeItem && (_b.active = Jb.activeItem += d.length)) : vb.append(d), K()
        }, sb.remove = function(b) {
            if (Rb) {
                var c = S(b);
                if (c > -1) {
                    Hb.eq(c).remove();
                    var d = c === Jb.activeItem;
                    null != Jb.activeItem && c < Jb.activeItem && (_b.active = --Jb.activeItem), K(), d && (_b.active = null, sb.activate(Jb.activeItem))
                }
            } else a(b).remove(), K()
        }, sb.moveAfter = function(a, b) {
            X(a, b, 1)
        }, sb.moveBefore = function(a, b) {
            X(a, b)
        }, sb.on = function(a, b) {
            if ("object" === e(a))
                for (var c in a) a.hasOwnProperty(c) && sb.on(c, a[c]);
            else if ("function" === e(b))
                for (var d = a.split(" "), f = 0, g = d.length; g > f; f++) $b[d[f]] = $b[d[f]] || [], -1 === Y(d[f], b) && $b[d[f]].push(b);
            else if ("array" === e(b))
                for (var h = 0, i = b.length; i > h; h++) sb.on(a, b[h])
        }, sb.one = function(a, b) {
            function c() {
                b.apply(sb, arguments), sb.off(a, c)
            }
            sb.on(a, c)
        }, sb.off = function(a, b) {
            if (b instanceof Array)
                for (var c = 0, d = b.length; d > c; c++) sb.off(a, b[c]);
            else
                for (var e = a.split(" "), f = 0, g = e.length; g > f; f++)
                    if ($b[e[f]] = $b[e[f]] || [], null == b) $b[e[f]].length = 0;
                    else {
                        var h = Y(e[f], b); - 1 !== h && $b[e[f]].splice(h, 1)
                    }
        }, sb.destroy = function() {
            return Sb.add(Ab).add(zb).add(Eb).add(Ub).add(Vb).add(Wb).add(Xb).add(Yb).add(Zb).off("." + r), u.off("keydown", kb), Wb.add(Xb).add(Yb).add(Zb).removeClass(rb.disabledClass), Hb && null != Jb.activeItem && Hb.eq(Jb.activeItem).removeClass(rb.activeClass), Eb.empty(), tb || (ub.off("." + r), Kb.restore(), Lb.restore(), Mb.restore(), Nb.restore(), a.removeData(b, r)), Ib.length = Gb.length = 0, _b = {}, sb.initialized = 0, sb
        }, sb.init = function() {
            if (!sb.initialized) {
                sb.on(q);
                var a = ["overflow", "position"],
                    b = ["position", "webkitTransform", "msTransform", "transform", "left", "top", "width", "height"];
                Kb.save.apply(Kb, a), Mb.save.apply(Mb, a), Lb.save.apply(Lb, b), Nb.save.apply(Nb, b);
                var c = Ab;
                return tb || (c = c.add(vb), ub.css("overflow", "hidden"), m || "static" !== ub.css("position") || ub.css("position", "relative")), m ? n && c.css(m, n) : ("static" === zb.css("position") && zb.css("position", "relative"), c.css({
                    position: "absolute"
                })), rb.forward && Ub.on(A, gb), rb.backward && Vb.on(A, gb), rb.prev && Wb.on(z, gb), rb.next && Xb.on(z, gb), rb.prevPage && Yb.on(z, gb), rb.nextPage && Zb.on(z, gb), Sb.on(y, ib), zb[0] && zb.on(z, jb), Rb && rb.activateOn && ub.on(rb.activateOn + "." + r, "*", lb), Eb[0] && rb.activatePageOn && Eb.on(rb.activatePageOn + "." + r, "*", mb), Tb.on(v, {
                    source: "slidee"
                }, bb), Ab && Ab.on(v, {
                    source: "handle"
                }, bb), u.on("keydown", kb), tb || (ub.on("mouseenter." + r + " mouseleave." + r, nb), ub.on("scroll." + r, h)), sb.initialized = 1, K(!0), rb.cycleBy && !tb && sb[rb.startPaused ? "pause" : "resume"](), sb
            }
        }
    }

    function e(a) {
        return null == a ? String(a) : "object" == typeof a || "function" == typeof a ? Object.prototype.toString.call(a).match(/\s([a-z]+)/i)[1].toLowerCase() || "object" : typeof a
    }

    function f(a, b) {
        a.preventDefault(), b && a.stopPropagation()
    }

    function g(b) {
        f(b, 1), a(this).off(b.type, g)
    }

    function h() {
        this.scrollLeft = 0, this.scrollTop = 0
    }

    function i(a) {
        return !isNaN(parseFloat(a)) && isFinite(a)
    }

    function j(a, b) {
        return 0 | G(String(a.css(b)).replace(/[^\-0-9.]/g, ""))
    }

    function k(a, b, c) {
        return b > a ? b : a > c ? c : a
    }

    function l(a) {
        var b = {};
        return b.style = {}, b.save = function() {
            if (a && a.nodeType) {
                for (var c = 0; c < arguments.length; c++) b.style[arguments[c]] = a.style[arguments[c]];
                return b
            }
        }, b.restore = function() {
            if (a && a.nodeType) {
                for (var c in b.style) b.style.hasOwnProperty(c) && (a.style[c] = b.style[c]);
                return b
            }
        }, b
    }
    var m, n, o, p = "sly",
        q = "Sly",
        r = p,
        s = b.cancelAnimationFrame || b.cancelRequestAnimationFrame,
        t = b.requestAnimationFrame,
        u = a(document),
        v = "touchstart." + r + " mousedown." + r,
        w = "mousemove." + r + " mouseup." + r,
        x = "touchmove." + r + " touchend." + r,
        y = (document.implementation.hasFeature("Event.wheel", "3.0") ? "wheel." : "mousewheel.") + r,
        z = "click." + r,
        A = "mousedown." + r,
        B = ["INPUT", "SELECT", "BUTTON", "TEXTAREA"],
        C = [],
        D = Math.abs,
        E = Math.sqrt,
        F = Math.pow,
        G = Math.round,
        H = Math.max,
        I = Math.min,
        J = 0;
    u.on(y, function(a) {
            var b = a.originalEvent[r],
                c = +new Date;
            (!b || b.options.scrollHijack < c - J) && (J = c)
        }),
        function(a) {
            function b(a) {
                var b = (new Date).getTime(),
                    d = Math.max(0, 16 - (b - c)),
                    e = setTimeout(a, d);
                return c = b, e
            }
            t = a.requestAnimationFrame || a.webkitRequestAnimationFrame || b;
            var c = (new Date).getTime(),
                d = a.cancelAnimationFrame || a.webkitCancelAnimationFrame || a.clearTimeout;
            s = function(b) {
                d.call(a, b)
            }
        }(window),
        function() {
            function a(a) {
                for (var d = 0, e = b.length; e > d; d++) {
                    var f = b[d] ? b[d] + a.charAt(0).toUpperCase() + a.slice(1) : a;
                    if (null != c.style[f]) return f
                }
            }
            var b = ["", "Webkit", "Moz", "ms", "O"],
                c = document.createElement("div");
            m = a("transform"), n = a("perspective") ? "translateZ(0) " : ""
        }(), b[q] = d, a.fn[p] = function(b, c) {
            var f, g;
            return a.isPlainObject(b) || (("string" === e(b) || b === !1) && (f = b === !1 ? "destroy" : b, g = Array.prototype.slice.call(arguments, 1)), b = {}), this.each(function(e, h) {
                var i = a.data(h, r);
                i || f ? i && f && i[f] && i[f].apply(i, g) : i = a.data(h, r, new d(h, b, c).init())
            })
        }, d.defaults = {
            slidee: null,
            horizontal: !1,
            itemNav: null,
            itemSelector: null,
            smart: !1,
            activateOn: null,
            activateMiddle: !1,
            scrollSource: null,
            scrollBy: 0,
            scrollHijack: 300,
            scrollTrap: !1,
            dragSource: null,
            mouseDragging: !1,
            touchDragging: !1,
            releaseSwing: !1,
            swingSpeed: .2,
            elasticBounds: !1,
            dragThreshold: 3,
            interactive: null,
            scrollBar: null,
            dragHandle: !1,
            dynamicHandle: !1,
            minHandleSize: 10,
            clickBar: !1,
            syncSpeed: .5,
            pagesBar: null,
            activatePageOn: null,
            pageBuilder: function(a) {
                return "<li>" + (a + 1) + "</li>"
            },
            forward: null,
            backward: null,
            prev: null,
            next: null,
            prevPage: null,
            nextPage: null,
            cycleBy: null,
            cycleInterval: 5e3,
            pauseOnHover: !1,
            startPaused: !1,
            moveBy: 300,
            speed: 0,
            easing: "swing",
            startAt: null,
            keyboardNavBy: null,
            draggedClass: "dragged",
            activeClass: "active",
            disabledClass: "disabled"
        }
}(jQuery, window);