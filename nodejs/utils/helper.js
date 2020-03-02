"use strict";

Date.prototype.format = function (format) {
  if (!format) format = 'yyyy-MM-dd hh:mm:ss';
  const o = {
    'M+': this.getMonth() + 1, // month
    'd+': this.getDate(), // day
    'h+': this.getHours(), // hour
    'm+': this.getMinutes(), // minute
    's+': this.getSeconds(), // second
    'q+': Math.floor((this.getMonth() + 3) / 3), // quarter
    S: this.getMilliseconds() // millisecond
  };
  if (/(y+)/.test(format)) {
    format = format.replace(RegExp.$1,
      (this.getFullYear() + '').substr(4 - RegExp.$1.length));
  }
  for (const k in o) {
    if (new RegExp('(' + k + ')').test(format)) {
      format = format.replace(RegExp.$1,
        RegExp.$1.length === 1 ? o[k] :
          ('00' + o[k]).substr(('' + o[k]).length));
    }
  }
  return format;
};
const date = {
  getMonthDay (date) {
    return (date || new Date()).format('MM-dd');
  },
  getDateTime (date) {
    return (date || new Date()).format('yyyy-MM-dd hh:mm:ss');
  }
};

export { date }

export function randomString (len) {
  len = len || 16;
  const $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
  /** **默认去掉了容易混淆的字符oOLl,9gq,Vv,Uu,I1****/
  const maxPos = $chars.length;
  let pwd = '';
  for (let i = 0; i < len; i++) {
    pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
  }
  return pwd;
}

String.prototype.random = randomString;


/**
 * Simple param, (cannot handle array)
 * @param {Object} data
 * @param {string|boolean} parent
 * @returns {string}
 */
export function param (data = {}, parent = false) {
  const arr = [];
  for (let key in data) {
    if (!data.hasOwnProperty(key)) continue;

    if (data[key] && typeof data[key] === 'object') {
      if (parent === false) {
        arr.push(param(data[key], encodeURIComponent(key)));
      } else {
        arr.push(param(data[key], parent + '[' + encodeURIComponent(key) + ']'));
      }
    } else {
      const value = encodeURIComponent((data[key] === null || data[key] === undefined) ? '' : data[key]);
      if (parent === false) {
        key = encodeURIComponent(key)
      } else {
        key = parent + '[' + encodeURIComponent(key) + ']'
      }
      arr.push(key + '=' + value)
    }
  }
  return arr.join('&');
}

export function sizeFormatter (byteSize) {
  byteSize = byteSize * 1;
  let i = 0;
  while (Math.abs(byteSize) >= 1024) {
    byteSize = byteSize / 1024;
    i++;
    if (i === 4)
      break
  }
  const units = [' B', ' KB', ' MB', ' GB', ' TB'];
  const newSize = byteSize.toFixed(2);
  return (newSize + units[i])
}

export async function sleep (timeout) {
  return new Promise(resolve => {
    setTimeout(resolve, timeout)
  })
}
