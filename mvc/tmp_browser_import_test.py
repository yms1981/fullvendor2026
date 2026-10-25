#!/usr/bin/env python3
"""Browser E2E: import product images (sample from Descargas/77) via CDP file set."""
import json
import os
import time
import traceback

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

SELENIUM = os.environ.get("SELENIUM_URL", "http://127.0.0.1:4444/wd/hub")
BASE = os.environ.get("APP_BASE", "http://test.birdviewmall.com")
MVC_IP = os.environ.get("MVC_IP", "172.18.0.6")
EMAIL = os.environ.get("FV_EMAIL", "bn@fullvendor.com")
PASSWORD = os.environ.get("FV_PASSWORD", "123456")
SAMPLE_DIR = os.environ.get("SAMPLE_DIR", "/files/img_import_sample")
SCREEN_DIR = os.environ.get("SCREEN_DIR", "/files/screens")
OUT_JSON = os.environ.get("OUT_JSON", "/files/import_test_result.json")


def log(m):
    print(m, flush=True)


def cdp(driver, cmd, params=None):
    params = params or {}
    if hasattr(driver, "execute_cdp_cmd"):
        return driver.execute_cdp_cmd(cmd, params)
    # Remote WebDriver — response may be raw or wrapped
    raw = driver.execute("executeCdpCommand", {"cmd": cmd, "params": params})
    if isinstance(raw, dict) and "value" in raw and isinstance(raw["value"], dict):
        return raw["value"]
    return raw


def dig(obj, *keys):
    cur = obj
    for k in keys:
        if not isinstance(cur, dict):
            return None
        cur = cur.get(k)
    return cur


def set_input_files(driver, css, files):
    cdp(driver, "DOM.enable", {})
    cdp(driver, "Runtime.enable", {})
    ev = cdp(
        driver,
        "Runtime.evaluate",
        {
            "expression": f'document.querySelector({json.dumps(css)})',
            "objectGroup": "fileinput",
        },
    )
    obj = (
        dig(ev, "result", "objectId")
        or dig(ev, "result", "result", "objectId")
        or dig(ev, "objectId")
    )
    node_id = None
    if obj:
        node = cdp(driver, "DOM.requestNode", {"objectId": obj})
        node_id = dig(node, "nodeId") or dig(node, "result", "nodeId")
    if not node_id:
        doc = cdp(driver, "DOM.getDocument", {"depth": 1})
        root_id = dig(doc, "root", "nodeId") or dig(doc, "result", "root", "nodeId")
        if not root_id:
            raise RuntimeError(f"CDP dig failed ev={str(ev)[:500]} doc={str(doc)[:500]}")
        node = cdp(driver, "DOM.querySelector", {"nodeId": root_id, "selector": css})
        node_id = dig(node, "nodeId") or dig(node, "result", "nodeId")
    if not node_id:
        raise RuntimeError(f"selector not found: {css}")
    cdp(driver, "DOM.setFileInputFiles", {"nodeId": node_id, "files": files})
    log(f"CDP setFileInputFiles ok node={node_id} n={len(files)}")


def main():
    os.makedirs(SCREEN_DIR, exist_ok=True)
    files = sorted(
        os.path.join(SAMPLE_DIR, f)
        for f in os.listdir(SAMPLE_DIR)
        if f.lower().endswith((".jpg", ".jpeg", ".png", ".jfif", ".webp", ".gif"))
    )
    log(f"sample_files={len(files)}")

    opts = Options()
    opts.add_argument("--headless=new")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--window-size=1440,1200")
    opts.add_argument(f"--host-resolver-rules=MAP test.birdviewmall.com {MVC_IP}")

    driver = webdriver.Remote(command_executor=SELENIUM, options=opts)
    wait = WebDriverWait(driver, 90)
    result = {"ok": False}

    try:
        driver.get(f"{BASE}/login")
        wait.until(EC.presence_of_element_located((By.NAME, "username"))).send_keys(EMAIL)
        driver.find_element(By.NAME, "password").send_keys(PASSWORD)
        driver.find_element(By.NAME, "password").submit()
        time.sleep(3)
        driver.get(f"{BASE}/company/products/importimages")
        wait.until(EC.presence_of_element_located((By.ID, "fv-prod-images-file-input")))
        driver.save_screenshot(f"{SCREEN_DIR}/02_import_page.png")
        log(f"on {driver.current_url}")

        # multi-file mode
        driver.execute_script(
            """
            const cb = document.getElementById('fv-prod-images-folder-mode');
            if (cb && cb.checked) cb.click();
            const input = document.getElementById('fv-prod-images-file-input');
            input.removeAttribute('webkitdirectory');
            input.removeAttribute('directory');
            input.multiple = true;
            """
        )
        time.sleep(0.5)
        set_input_files(driver, "#fv-prod-images-file-input", files)
        # ensure change handlers run
        driver.execute_script(
            """
            const input = document.getElementById('fv-prod-images-file-input');
            const ev = new Event('change', { bubbles: true });
            input.dispatchEvent(ev);
            """
        )
        log("CDP files set; waiting SKU match…")

        queue_count = 0
        deadline = time.time() + 180
        while time.time() < deadline:
            info = driver.execute_script(
                """
                const api = window.fvProdImagesImportApi;
                const q = api && api.getImportableFiles ? api.getImportableFiles() : [];
                return {
                  queue: (q || []).length,
                  inputFiles: document.getElementById('fv-prod-images-file-input').files.length,
                  checks: document.querySelectorAll('.fv-prod-images-file-check').length
                };
                """
            )
            queue_count = max(int(info.get("queue") or 0), int(info.get("checks") or 0))
            if queue_count:
                log(f"queue ready: {info}")
                break
            if int(time.time()) % 10 == 0:
                log(f"waiting… {info}")
            time.sleep(1)

        driver.save_screenshot(f"{SCREEN_DIR}/03_after_select.png")
        result["match"] = {
            "queue_count": queue_count,
            "body": driver.find_element(By.TAG_NAME, "body").text[:2500],
        }
        log(f"queue={queue_count}")

        clicked = driver.execute_script(
            """
            const b = document.getElementById('btn-export');
            if (!b) return null;
            b.scrollIntoView({block:'center'});
            b.click();
            return (b.innerText || b.value || 'btn-export').trim();
            """
        )
        log(f"import click #btn-export: {clicked!r}")

        if queue_count and clicked:
            deadline = time.time() + 600
            last_key = None
            best = None
            while time.time() < deadline:
                panels = driver.execute_script(
                    """
                    function txt(id){
                      const el = document.getElementById(id);
                      return el ? el.innerText : '';
                    }
                    function cnt(id){
                      const el = document.getElementById(id);
                      if (!el) return 0;
                      return el.querySelectorAll('.fv-category-images-import__result-item,.fv-category-images-import__result-file,li').length;
                    }
                    const summary = window.fvProdImagesImportApi && window.fvProdImagesImportApi.getUploadSummary
                      ? window.fvProdImagesImportApi.getUploadSummary() : null;
                    const prog = document.getElementById('fv-prod-images-upload-progress');
                    return {
                      up: txt('resultado_subidos'),
                      upn: cnt('resultado_subidos'),
                      no: txt('resultado_nosubidos'),
                      non: cnt('resultado_nosubidos'),
                      okCount: (document.getElementById('fv-prod-img-count-ok')||{}).innerText || '',
                      errCount: (document.getElementById('fv-prod-img-count-error')||{}).innerText || '',
                      busy: !!(document.querySelector('.swal2-container')) || (prog && !prog.classList.contains('d-none')),
                      btnDisabled: !!document.getElementById('btn-export')?.disabled,
                      summary
                    };
                    """
                )
                key = (panels["upn"], panels["non"], panels.get("busy"), panels.get("btnDisabled"), panels.get("okCount"), str(panels.get("summary")))
                if key != last_key:
                    last_key = key
                    log(f"progress up={panels['upn']} no={panels['non']} okCount={panels.get('okCount')} busy={panels['busy']} btnDisabled={panels.get('btnDisabled')} summary={panels.get('summary')}")
                if (panels["upn"] > 0 or panels.get("okCount") not in ("", "0", None)) and (
                    best is None or panels["upn"] >= best.get("upn", 0)
                ):
                    best = dict(panels)
                    driver.save_screenshot(f"{SCREEN_DIR}/04_after_import.png")
                done_count = panels["upn"] + panels["non"]
                summary = panels.get("summary") or {}
                uploads_done = summary.get("done", 0) + summary.get("error", 0)
                if (
                    done_count >= queue_count
                    and not panels["busy"]
                    and not panels.get("btnDisabled")
                ):
                    break
                # uploads finished and link phase produced counters
                if (
                    uploads_done >= queue_count
                    and not panels["busy"]
                    and not panels.get("btnDisabled")
                    and (panels.get("okCount") not in ("", "0") or done_count > 0)
                ):
                    # give a moment for final paint
                    time.sleep(2)
                    break
                time.sleep(2)

            time.sleep(1)
            driver.save_screenshot(f"{SCREEN_DIR}/05_final.png")
            final = driver.execute_script(
                """
                return {
                  okCount: (document.getElementById('fv-prod-img-count-ok')||{}).innerText || '',
                  errCount: (document.getElementById('fv-prod-img-count-error')||{}).innerText || '',
                  up: (document.getElementById('resultado_subidos')||{}).innerText || '',
                  no: (document.getElementById('resultado_nosubidos')||{}).innerText || ''
                };
                """
            )
            panels = best or final
            result["import"] = {"best": best, "final": final}
            blob = json.dumps(result["import"]).lower()
            result["has_slot_message"] = "slot" in blob
            ok_num = 0
            try:
                ok_num = int(str((best or {}).get("okCount") or (best or {}).get("upn") or 0).replace(",", "").replace(".", ""))
            except Exception:
                ok_num = int((best or {}).get("upn") or 0)
            result["ok"] = ok_num > 0 or ((best or {}).get("upn") or 0) > 0
            log(f"DONE ok={result['ok']} slot_msg={result['has_slot_message']}")
            log("BEST:\n" + json.dumps(best, ensure_ascii=False)[:2500])
            log("FINAL:\n" + json.dumps(final, ensure_ascii=False)[:1500])
        else:
            result["import"] = {"skipped": True, "queue_count": queue_count, "clicked": clicked}
            result["dom"] = driver.execute_script(
                """
                return {
                  files: document.getElementById('fv-prod-images-file-input')?.files?.length,
                  folder: document.getElementById('fv-prod-images-folder-mode')?.checked,
                  api: !!window.fvProdImagesImportApi,
                  checked: document.querySelectorAll('.fv-prod-images-file-check:checked').length
                };
                """
            )
            log(f"skipped import dom={result['dom']}")

        driver.save_screenshot(f"{SCREEN_DIR}/05_final.png")
    except Exception as e:
        result["error"] = str(e)
        result["trace"] = traceback.format_exc()
        try:
            driver.save_screenshot(f"{SCREEN_DIR}/error.png")
        except Exception:
            pass
        log(result["trace"])
    finally:
        with open(OUT_JSON, "w", encoding="utf-8") as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
        driver.quit()


if __name__ == "__main__":
    main()
