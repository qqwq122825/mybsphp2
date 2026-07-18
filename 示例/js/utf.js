function utf16to8(r) {
    var a, e, o, t;
    for (a = "",
    o = r.length,
    e = 0; e < o; e++)
        (t = r.charCodeAt(e)) >= 1 && t <= 127 ? a += r.charAt(e) : t > 2047 ? (a += String.fromCharCode(224 | t >> 12 & 15),
        a += String.fromCharCode(128 | t >> 6 & 63),
        a += String.fromCharCode(128 | t >> 0 & 63)) : (a += String.fromCharCode(192 | t >> 6 & 31),
        a += String.fromCharCode(128 | t >> 0 & 63));
    return a
}
function utf8to16(r) {
    var a, e, o, t, c, C;
    for (a = "",
    o = r.length,
    e = 0; e < o; )
        switch ((t = r.charCodeAt(e++)) >> 4) {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
        case 7:
            a += r.charAt(e - 1);
            break;
        case 12:
        case 13:
            c = r.charCodeAt(e++),
            a += String.fromCharCode((31 & t) << 6 | 63 & c);
            break;
        case 14:
            c = r.charCodeAt(e++),
            C = r.charCodeAt(e++),
            a += String.fromCharCode((15 & t) << 12 | (63 & c) << 6 | (63 & C) << 0)
        }
    return a
}
