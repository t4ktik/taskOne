import _ from 'lodash';
import $ from 'jquery';
import Popper from 'popper.js';
import axios from 'axios';

window._ = _;
window.Popper = Popper.default || Popper;
window.$ = window.jQuery = $;

import 'bootstrap';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
