/**
 * Bootstrap 4's jQuery plugins expect jQuery, Popper, and lodash to be present
 * on `window` at the moment its module is evaluated. Assigning them here — in a
 * module imported *before* `bootstrap` — guarantees that ordering, since ES
 * modules are evaluated to completion in import order.
 */
import _ from 'lodash';
import Popper from 'popper.js';
import jQuery from 'jquery';

window._ = _;
window.Popper = Popper;
window.$ = window.jQuery = jQuery;
