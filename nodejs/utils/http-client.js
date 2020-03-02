import axios from 'axios'
import { param, randomString } from '@/utils/helper'

export function createHttpClient (baseURL, token) {
  const service = axios.create({
    baseURL,  // api的base_url
    timeout: 15000, // 请求超时时间
    transformRequest: [data => {
      // Do whatever you want to transform the data
      // console.debug(data);
      if (data && data.constructor !== global['FormData']) data = param(data);
      // console.debug(data);
      return data;
    }]
  });

  // request拦截器
  service.interceptors.request.use(config => {
    config.headers['X-Server-Token'] = token;
    config.headers['X-XSRF-Token'] = randomString(32); // 假装有 csrf
    config.headers['X-Requested-With'] = 'XMLHttpRequest';
    return config;
  }, error => {
    // Do something with request error
    // console.log(error); // for debug
    Promise.reject(error);
  });

  // response 拦截器
  service.interceptors.response.use(
    response => {
      return response.data
    },
    error => {
      if (error.response && error.response.data) {
        if (error.response.data.message) {
          return Promise.reject(error.response.data);
        }
        return Promise.reject({
          message: JSON.stringify(error.response.data)
        });
      } else {
        console.error('fetch.error:');
        console.error(error);
        return Promise.reject({
          message: error.response ? error.response.statusText : error.message
        });
      }
    }
  );
  return service;
}
