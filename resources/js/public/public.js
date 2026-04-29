import '../bootstrap';

import { scrollPage } from './scroll-page.js';
import { toggleMenu } from './toggle-menu.js';
import { checkFilling } from './check-filling.js';
import { accordion } from './accordion.js';
import { modal } from './modal.js';

import { categoriesControl } from './news/categories-control.js';
import { paginationControl } from './news/pagination-control.js';

import { contactForm } from './contact-form.js';
import { projectGalleryCarousel } from './project/gallery-carousel.js';


scrollPage();
toggleMenu();
checkFilling();
accordion();
modal();

categoriesControl();
paginationControl();

contactForm();
projectGalleryCarousel();
