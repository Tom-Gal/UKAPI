import type { LegalDocument } from '../pages/public/legal';

const termsOfUse: LegalDocument = {
    title: 'Terms of Use',
    summary:
        'The terms that apply when you create an account or use the UKAPI website and API.',
    sections: [
        {
            title: 'Agreement to our Legal Terms',
            paragraphs: [
                'UKAPI operates https://ukapi.co.uk and related products and services that refer or link to these Legal Terms. These Terms are a legally binding agreement between you, personally or on behalf of an entity, and UKAPI concerning access to and use of the Services.',
                'By accessing the Services, you confirm that you have read, understood, and agreed to these Legal Terms and any supplemental terms or documents incorporated by reference. If you do not agree with all of these Legal Terms, you must stop using the Services immediately.',
                'We may update these Legal Terms from time to time by changing the Last updated date. You are responsible for reviewing them periodically, and continued use after changes are posted means you accept the revised Terms. The Services are intended only for people at least 18 years old.',
            ],
        },
        {
            title: '1. Our Services',
            paragraphs: [
                'UKAPI is an independent developer service. It is not part of, authorised by, or endorsed by any government department, public body, or other data Source.',
                'UKAPI collects, standardises, and delivers data from public UK Sources and selected first-party APIs. Each dataset has its own Source, licence, and attribution requirements. The applicable Source Licence governs what you may do with Data; these Terms govern how you access the Service.',
                'Our website and API provide general information, not legal, financial, medical, planning, safety, or other professional advice. Check the original Source before relying on Data for an important decision.',
                'Information provided through the Services is not intended for distribution or use where doing so would breach local law or create a registration requirement. People who access the Services from other locations do so on their own initiative and are responsible for compliance with applicable local laws.',
            ],
        },
        {
            title: '2. Intellectual property rights',
            paragraphs: [
                'We own or license the intellectual property rights in the Services, including source code, databases, functionality, software, website designs, audio, video, text, photographs, graphics, trademarks, service marks, and logos. Our Content and Marks are protected by copyright, trademark, and other applicable laws and treaties.',
                'Subject to these Terms and the Acceptable Use Policy, we grant you a non-exclusive, non-transferable, revocable licence to access the Services and download or print portions of Content to which you have properly gained access for personal, non-commercial use or an internal business purpose.',
                'Except as expressly permitted, you may not copy, reproduce, aggregate, republish, upload, post, publicly display, encode, translate, transmit, distribute, sell, license, or commercially exploit any part of the Services, Content, or Marks without our prior written permission. If permission is granted, identify UKAPI or its licensors and preserve copyright and proprietary notices.',
                'We reserve all rights not expressly granted. A breach of these intellectual property rights is a material breach and ends your right to use the Services immediately.',
            ],
        },
        {
            title: '3. User representations',
            paragraphs: [
                'You represent and warrant that registration information will be true, accurate, current, and complete; you will keep it accurate; you have legal capacity to agree to these Terms; you are not a minor where you live; you will not access the Services through unauthorised automated or non-human means; you will not use the Services for an illegal or unauthorised purpose; and your use will not violate applicable law or regulation.',
                'If you provide information that is untrue, inaccurate, not current, or incomplete, we may suspend or terminate your account and refuse current or future use of the Services.',
            ],
        },
        {
            title: '4. User registration, accounts, and API keys',
            paragraphs: [
                'You may need to register to use parts of the Services. Keep passwords, login credentials, registration details, and API keys confidential and accurate. You are responsible for use of your account and password.',
                'Do not share API keys except with people working on your application, publish them in public code, misuse another person’s account or key, or create multiple accounts to evade a limit. We may remove, reclaim, or change a username that is inappropriate, obscene, or objectionable.',
            ],
        },
        {
            title: '5. Purchases and payment',
            paragraphs: [
                'Current paid Plans, features, usage limits, and prices are shown on the Pricing page. Prices are in pounds sterling and the total payable, including VAT where applicable, is shown before purchase. You pay for access to the Service, not ownership of Data.',
                'Provide current, complete, and accurate purchase and account information and promptly update your email, payment method, and payment-card expiry information. Payment is processed through Stripe; UKAPI does not store full card details. We may correct pricing errors, refuse orders, or limit quantities where appropriate.',
                'Your paid Plan contract starts when we confirm your purchase by email. Amounts already due remain payable. Paid Plan billing, renewal, cancellation, refunds, and consumer rights are also covered by the Paid Plan Terms.',
            ],
        },
        {
            title: '6. Subscriptions',
            paragraphs: [
                'Paid Plans are billed monthly or annually in advance and renew automatically for the same period until cancelled. Upgrades take effect immediately; cancellation takes effect at the end of the period already paid for and access continues until then.',
                'If a payment fails, we will email you and retry it. If payment still cannot be collected after warnings, we may suspend the paid Plan and move the account to the free Plan. Annual Plans and renewal terms are shown before purchase and reminders are sent where required by law.',
                'We may change prices with at least 30 days’ written notice, effective only from the next renewal. You can cancel before the change takes effect without being charged the new price. Consumers may have statutory cancellation and refund rights described in the Paid Plan Terms.',
            ],
        },
        {
            title: '7. Prohibited activities',
            paragraphs: [
                'You must follow the Acceptable Use Policy. Do not attack, disrupt, degrade, overload, scan, probe, or security-test the Service without permission; ignore Rate Limits, Retry-After headers, or HTTP errors; misuse credentials; evade quotas or billing; or use the Service as a competing proxy without written permission.',
                'Do not systematically retrieve Data to create a database or directory without written permission; trick, defraud, or mislead us or users; bypass security or access controls; frame or link without authorisation; misuse support; submit false abuse reports; impersonate another user; harass or harm another person; or use the Services for an unauthorised commercial purpose.',
                'Do not upload malware, spam, repetitive text, tracking mechanisms, or other material that interferes with the Services; remove proprietary notices; copy or adapt our software; reverse engineer except where law permits; use unauthorised scrapers or automation; use buying agents; or use the Services in breach of law.',
                'Automated tools, including machine-learning tools, may use Data only as far as the applicable Source Licence allows and must observe Rate Limits, bulk-export guidance, attribution requirements, and an identifying User-Agent.',
            ],
        },
        {
            title: '8. User-generated contributions',
            paragraphs: [
                'The Services may allow you to submit questions, comments, suggestions, ideas, feedback, or other content. Contributions may be visible to other users or through third-party websites and may be handled under the Privacy Notice.',
                'You represent that Contributions do not infringe intellectual property, privacy, publicity, or other rights; you own them or have the necessary licences and permissions; identifiable people have provided required consent; and Contributions are accurate and not unlawful, misleading, abusive, hateful, obscene, discriminatory, threatening, sexually explicit, spam, unauthorised advertising, or otherwise objectionable.',
                'You are responsible for your Contributions and agree to reimburse us for losses caused by your breach of this section, third-party rights, or applicable law.',
            ],
        },
        {
            title: '9. Contribution licence',
            paragraphs: [
                'By sending feedback or other Submissions about the Services, you assign to UKAPI the intellectual property rights in those Submissions and agree that we may use and disseminate them for any lawful purpose without acknowledgement or compensation.',
                'You waive moral rights to the extent permitted by law, warrant that Submissions are original or properly licensed, have authority to grant these rights, and do not contain confidential information.',
            ],
        },
        {
            title: '10. Third-party websites and content',
            paragraphs: [
                'The Services may contain links to third-party websites, services, or Content. We do not monitor, verify, or guarantee third-party websites or Content, and a link does not imply endorsement. Your use of third-party services is at your own risk and subject to their terms and privacy policies.',
            ],
        },
        {
            title: '11. Services management',
            paragraphs: [
                'We reserve the right, but not the obligation, to monitor the Services for breaches of these Terms, take appropriate legal action, refuse or limit access, remove or disable Content, and manage the Services in a way that protects our rights, property, users, and operations.',
            ],
        },
        {
            title: '12. Privacy policy',
            paragraphs: [
                'Our Privacy Notice explains how we collect, use, share, protect, and retain personal information. By using the Services, you agree to the processing described there, subject to your rights and choices.',
            ],
        },
        {
            title: '13. Term and termination',
            paragraphs: [
                'These Terms remain in effect while you use the Services. We may suspend or restrict access for serious Acceptable Use Policy or Rate Limit breaches, compromised keys, legal or Source requirements, unpaid amounts, or other material breaches, and will explain what happened where we can.',
                'You may close your account at any time. Paid Plan cancellation is covered by the Paid Plan Terms. On termination, your right to use the Services ends, but provisions that should survive termination, including intellectual property, disclaimers, liability limits, indemnification, and dispute terms, continue to apply.',
            ],
        },
        {
            title: '14. Modifications and interruptions',
            paragraphs: [
                'We may add, change, or remove features, Plans, or datasets when a Source changes its publication, format, licence, or access, or when law or security requires it. We will give notice of Breaking Changes where reasonably possible.',
                'We cannot promise that the Services will always be available, uninterrupted, secure, or error-free. We may experience delays, maintenance, outages, or interruptions outside our control.',
            ],
        },
        {
            title: '15. Governing law',
            paragraphs: [
                'These Terms are governed by the law of England and Wales, subject to mandatory consumer rights to bring proceedings where the consumer lives.',
            ],
        },
        {
            title: '16. Dispute resolution',
            paragraphs: [
                'If a dispute arises, the parties should first try to resolve it through good-faith informal negotiations. This does not prevent a consumer from using statutory rights, bringing proceedings in a competent court, or seeking urgent relief where appropriate.',
            ],
        },
        {
            title: '17. Corrections',
            paragraphs: [
                'Information on the Services may contain typographical errors, inaccuracies, or omissions, including in descriptions, pricing, availability, and other details. We may correct errors and update information at any time without prior notice.',
            ],
        },
        {
            title: '18. Disclaimer',
            paragraphs: [
                'The Services and Data are provided on an as-available and as-is basis. We take reasonable care to deliver Data as published by its Source, but Data may be delayed, cached, reformatted, incomplete, or inaccurate. Check the original Source before relying on it for an important decision.',
                'Nothing in this disclaimer excludes a warranty or right that cannot legally be excluded, including statutory consumer rights.',
            ],
        },
        {
            title: '19. Limitations of liability',
            paragraphs: [
                'Nothing limits liability that cannot legally be limited, including death or personal injury caused by negligence, fraud, or statutory consumer rights. Subject to that, business-user liability exclusions and any contractual cap apply as set out in the full agreement.',
                'We are not responsible for indirect or consequential loss, loss of profits, revenue, data, goodwill, or business interruption to the extent permitted by law. You should not rely on the Services for decisions where an error could cause serious harm without checking the Source.',
            ],
        },
        {
            title: '20. Indemnification',
            paragraphs: [
                'To the extent permitted by law, you agree to defend, indemnify, and hold UKAPI and its officers, employees, agents, and service providers harmless from claims, losses, liabilities, damages, costs, and expenses arising from your breach of these Terms, misuse of the Services, Contributions, or violation of another person’s rights or applicable law.',
            ],
        },
        {
            title: '21. User data',
            paragraphs: [
                'You are responsible for the accuracy, legality, and rights required for information you submit through the Services. API requests may contain a postcode or place name, but do not include another person’s personal information. API request payloads are not retained for operational purposes.',
            ],
        },
        {
            title: '22. Electronic communications, transactions, and signatures',
            paragraphs: [
                'You consent to receive communications electronically, including by email and notices posted through the Services. Electronic communications, agreements, notices, and records satisfy legal writing requirements where permitted by law.',
            ],
        },
        {
            title: '23. Miscellaneous',
            paragraphs: [
                'If a provision is unlawful or unenforceable, it will be modified or severed to the minimum extent necessary and the remaining provisions will continue. No waiver is effective unless written. These Terms and incorporated policies are the entire agreement about the Services and replace prior understandings. You may not assign your rights without consent; we may assign ours in a reorganisation or transfer of the business.',
            ],
        },
        {
            title: '24. Contact us',
            paragraphs: [
                'Questions about these Terms, the Services, or Data can be sent to the legal or support contact shown in the footer. You may also contact UKAPI by email at contact@ukapi.co.uk.',
            ],
        },
    ],
};

export default termsOfUse;
