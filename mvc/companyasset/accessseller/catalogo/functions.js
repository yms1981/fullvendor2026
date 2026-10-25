import { create_rowcustomer} from "./create_rowcustomer.js?v1=0.0.1";
import { get_collapseElementList } from "./get_collapseElementList.js";
import { update_collapseElementList } from "./update_collapse.js?v1=0.0.1";
import { tab_index } from "./tab-index.js?v1=0.0.1";
import { search_customer } from "./search_customer.js";
import { clean_customers_panel } from "./clean_customer_panel.js";
import { get_customerbyscroll } from './scroll-more-customers.js';

const methods = {
    create_rowcustomer,
    get_collapseElementList,
    update_collapseElementList,
    tab_index,
    search_customer,
    clean_customers_panel,
    get_customerbyscroll
}

export default methods