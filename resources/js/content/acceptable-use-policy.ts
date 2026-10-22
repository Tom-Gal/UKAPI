import type { LegalDocument } from '../pages/public/legal';

const acceptableUsePolicy: LegalDocument = {
    title: 'Acceptable Use Policy',
    summary:
        'Rules for accessing UKAPI fairly, securely, and consistently with applicable law and Source Licences.',
    sections: [
        {
            title: 'Who we are',
            paragraphs: [
                'UKAPI operates https://ukapi.co.uk and related products and services that refer or link to this Policy. This Policy applies to all uses of our Services and all forms, materials, comments, posts, and other Content made available through them.',
                'This Policy forms part of our Legal Terms. If you do not agree with the Legal Terms or this Policy, do not use the Services. Continued use means you accept them.',
            ],
        },
        {
            title: 'Use of the Services',
            paragraphs: [
                'When you use the Services, you warrant that you will comply with this Policy and all applicable laws.',
                'Do not systematically retrieve data or other Content to create or compile a collection, compilation, database, or directory without our written permission.',
                'Do not make unauthorised use of the Services, collect user names or email addresses to send unsolicited email, or create accounts by automated means or under false pretences.',
                'Do not circumvent, disable, or interfere with security features, features that restrict copying or use of Content, or limitations on the Services or Content.',
                'Do not frame or link to the Services without authorisation, trick or mislead us or other users, seek sensitive account information such as passwords, misuse support services, or submit false abuse or misconduct reports.',
                'Do not use scripts to send comments or messages, data-mining or similar extraction tools, or other automated systems such as spiders, robots, scrapers, cheat utilities, or offline readers, except for ordinary search-engine or browser use and authorised API access.',
                'Do not interfere with, disrupt, or create an undue burden on the Services or connected networks; impersonate another person; use information from the Services to harass, abuse, or harm another person; or use another user’s username.',
                'Do not use the Services to compete with us or use the Services or Content for a revenue-generating endeavour or commercial enterprise unless expressly permitted by the applicable Plan, Source Licence, or written agreement.',
                'Do not decipher, decompile, disassemble, or reverse engineer software that forms part of the Services except where applicable law expressly permits it; bypass access controls; harass, intimidate, or threaten staff or agents; or remove copyright or proprietary notices.',
                'Do not copy or adapt the Services’ software, including Flash, PHP, HTML, JavaScript, or other code, except as expressly permitted by law or written agreement.',
                'Do not upload or transmit viruses, Trojan horses, malware, spam, repetitive text, excessive capital letters, or other material that interferes with another party’s use of the Services or modifies, impairs, disrupts, alters, or interferes with their operation or maintenance.',
                'Do not upload or transmit passive or active information-collection mechanisms, including clear GIFs, 1×1 pixels, web bugs, cookies, spyware, or similar devices, except where part of an authorised Service feature.',
                'Do not disparage, tarnish, or otherwise harm us or the Services, or use the Services in a manner inconsistent with applicable laws or regulations.',
            ],
        },
        {
            title: 'Subscriptions and API access',
            paragraphs: [
                'If you subscribe to our Services, do not modify, copy, redistribute, publish, display, perform, or retransmit any portion of the Services except as expressly permitted by this Policy, the applicable Plan, or a written agreement with UKAPI.',
                'Do not damage, reveal, or alter another user’s data or hardware, software, or information; misuse another person’s account or API key; bypass account quotas or Rate Limits; or ignore Retry-After headers or HTTP errors.',
                'Do not attack, degrade, overload, scan, probe, or security-test the Services without permission. Do not conceal your identity to evade controls, break the law, infringe rights, misstate a Source or imply endorsement, remove required attribution, identify individuals from Data, or resell the Service itself as a competing API without written permission.',
            ],
        },
        {
            title: 'Automated tools and AI',
            paragraphs: [
                'You may use Data in automated tools, including training or evaluating machine-learning models, only as far as the applicable Source Licence permits. Follow Rate Limits, use bulk exports for large volumes, provide required attribution, and identify automated clients in the User-Agent with a contact address.',
            ],
        },
        {
            title: 'Consequences of breaching this Policy',
            paragraphs: [
                'Consequences vary depending on the severity of the breach and your history on the Services. We may give a warning, but if a breach is serious or continues, we may suspend or terminate access, disable an account, or revoke an API key.',
                'We may notify law enforcement or bring legal proceedings when we believe there is a genuine risk to an individual or a threat to public safety. We exclude liability for actions taken in response to breaches of this Policy to the extent permitted by law.',
                'Where possible, we will start with a warning or throttling. We may suspend or revoke a key immediately where a breach is serious or risks the Services or other users. If you think we have made a mistake, contact support and we will review it.',
            ],
        },
        {
            title: 'Reporting and enforcement',
            paragraphs: [
                'Report a vulnerability, data that appears inaccurate, infringes rights, or identifies a person to contact@ukapi.co.uk.',
                'Good-faith researchers who minimise access, avoid disruption, and allow reasonable remediation time will not be pursued under this Policy.',
            ],
        },
        {
            title: 'How can you contact us about this Policy?',
            paragraphs: [
                'If you have questions or comments about this Policy, email contact@ukapi.co.uk.',
            ],
        },
    ],
};

export default acceptableUsePolicy;
