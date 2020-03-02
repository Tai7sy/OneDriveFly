"use strict";

import * as Logger from "noogger";

if (process.env.NODE_ENV === 'development') {
    require('source-map-support').install({
        environment: 'node',
        hookRequire: true
    });
}

import * as fs from 'fs';
import * as path from 'path';
import * as Listener from './listener';
