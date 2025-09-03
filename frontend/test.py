import requests

url = "https://ums.lpu.in/lpuums/StudentDashboard.aspx/ViewAllMessages"

headers = {
    "Accept": "application/json, text/javascript, */*; q=0.01",
    "Accept-Language": "en-US,en;q=0.9",
    "Connection": "keep-alive",
    "Content-Type": "application/json; charset=UTF-8",
    "Origin": "https://ums.lpu.in",
    "Referer": "https://ums.lpu.in/lpuums/StudentDashboard.aspx",
    "Sec-Fetch-Dest": "empty",
    "Sec-Fetch-Mode": "cors",
    "Sec-Fetch-Site": "same-origin",
    "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
    "X-Requested-With": "XMLHttpRequest",
    "sec-ch-ua": '"Not)A;Brand";v="8", "Chromium";v="138", "Google Chrome";v="138"',
    "sec-ch-ua-mobile": "?0",
    "sec-ch-ua-platform": '"macOS"',
}

cookies = {
    "_fbp": "fb.1.1751087203615.60893696959034888",
    "_gcl_au": "1.1.299669889.1751087204",
    "_ga_S234BK01XY": "GS2.2.s1754150385$o7$g1$t1754150402$j43$l0$h0",
    "_ga_NL1Q5STRF3": "GS2.1.s1754152542$o12$g0$t1754152542$j60$l0$h0",
    "_ga_0CLNCH2T5Y": "GS2.1.s1754152542$o12$g0$t1754152542$j60$l0$h1032110685",
    "_gid": "GA1.2.831614768.1754672949",
    "_ga_WKLQCVXZ47": "GS2.1.s1754673200$o14$g0$t1754673200$j60$l0$h0",
    "_clck": "9owwbk%7C2%7Cfya%7C0%7C2005",
    "cf_clearance": "y83DpHg9oxF7jkmkYzP0Jpoh1Cr1d.NPb1m3BeC2lak-1754673206-1.2.1.1-L0OY5grSHlJaPgzcjdI5OfMrhPDcpp0WCA4CRbVEApruVlOS1RwCCvOra1j_JWamYGaZwecuxokGCSoK0NRY1.KdafOi8lNwmekOT55kGmAq8d8AaxrEncqeVh1cvLN5f27E.I6ovCaJrvl8QMrf1n7Vx5ef34jLOd__U.dVYMNoOn.5C8KsUBqE3nqCQDKxzi.u5U3TG8uDEe0hbi04M5qceGR5FRtnTZmLho8_wIE",
    "_uetvid": "dbcd3fc053dd11f0b43c6fc813a23e00|1x9zra8|1754673213245|1|1|bat.bing.com/p/insights/c/n",
    "_ga": "GA1.2.871311711.1751087204",
    "ASP.NET_SessionId": "04wqe5gntp5iovfhhvd0lvcx",
    "_gat": "1",
    "_ga_B0Z6D6GCD8": "GS2.2.s1754762125$o7$g1$t1754763882$j45$l0$h0",
}

payload = {}

response = requests.post(url, headers=headers, cookies=cookies, json=payload)

print(response.status_code)
print(response.text)
