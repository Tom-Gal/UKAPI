import type { LegalDocument } from '../pages/public/legal';

const privacyPolicy: LegalDocument = {
    title: 'Privacy Notice',
    summary:
        'How UKAPI collects, uses, shares, protects, and retains personal information when you use its services.',
    sections: [
        {
            title: '1. What information do we collect?',
            paragraphs: [
                'We collect personal information that you voluntarily provide when you register for the Services, express an interest in us or our products and Services, participate in activities on the Services, or contact us.',
                'Personal information provided by you may include email addresses, billing addresses, debit or credit card numbers, contact preferences, contact or authentication data, and passwords.',
                'We do not process sensitive information.',
                'We may collect data necessary to process payments, such as your payment instrument number and its security code. Payment data is handled and stored by Stripe. See Stripe’s privacy notice at https://stripe.com/gb/privacy.',
                'All personal information you provide must be true, complete, and accurate, and you must notify us of changes.',
                'Some information, such as your Internet Protocol (IP) address and browser and device characteristics, is collected automatically when you visit our Services. We may also collect operating system, language preferences, referring URLs, device name, country, location, and information about how and when you use our Services.',
                'Log and usage data may include your IP address, device information, browser type and settings, activity timestamps, pages and files viewed, searches, features used, error reports, and hardware settings.',
                'Device data may include your IP address or proxy server, device and application identification numbers, location, browser type, hardware model, internet service provider or mobile carrier, operating system, and system configuration information.',
            ],
        },
        {
            title: '2. How do we process your information?',
            paragraphs: [
                'We process your personal information for a variety of reasons, depending on how you interact with our Services.',
                'To facilitate account creation and authentication and manage user accounts. Retention period: while the account is active, then within 12 months after account closure.',
                'To deliver and facilitate delivery of Services. Retention period: while the account is active, then within 12 months after account closure. API request payloads are not retained.',
                'To respond to user enquiries and offer support. Retention period: while the account is active, then within 12 months after account closure.',
                'To send administrative information, fulfil and manage orders, request feedback, send marketing and promotional communications, deliver targeted advertising, protect our Services, identify usage trends, and measure the effectiveness of marketing campaigns. Retention period for each purpose: while the account is active, then within 12 months after account closure.',
                'To save or protect an individual’s vital interest when necessary to prevent harm.',
            ],
        },
        {
            title: '3. What legal bases do we rely on to process your information?',
            paragraphs: [
                'The General Data Protection Regulation and UK GDPR require us to explain the valid legal bases we rely on to process personal information.',
                'Consent. We may process information if you have given us permission to use it for a specific purpose. You can withdraw consent at any time.',
                'Performance of a contract. We may process personal information when necessary to fulfil our contractual obligations, including providing our Services or acting at your request before entering into a contract.',
                'Legitimate interests. We may process information where reasonably necessary to achieve our legitimate business interests and those interests do not outweigh your interests and fundamental rights and freedoms. Examples include sending information about offers, developing relevant advertising, analysing use of our Services, supporting marketing, diagnosing problems, preventing fraud, and improving user experience.',
                'Legal obligations. We may process information where necessary to comply with legal obligations, cooperate with law enforcement or regulators, exercise or defend legal rights, or disclose information as evidence in litigation.',
                'Vital interests. We may process information where necessary to protect your vital interests or those of a third party, such as where someone’s safety may be at risk.',
            ],
        },
        {
            title: '4. When and with whom do we share your personal information?',
            paragraphs: [
                'We may need to share personal information in specific situations, including business transfers. We may share or transfer information in connection with, or during negotiations of, a merger, sale of company assets, financing, or acquisition of all or part of our business by another company.',
            ],
        },
        {
            title: '5. Do we use cookies and other tracking technologies?',
            paragraphs: [
                'We may use cookies and similar tracking technologies, such as web beacons and pixels, to gather information when you interact with our Services. These technologies help maintain the security of our Services and your account, prevent crashes, fix bugs, save preferences, and support basic site functions.',
                'We also permit third parties and service providers to use online tracking technologies for analytics and advertising, including to tailor advertisements to your interests. Specific information about how we use these technologies and how you can refuse certain cookies is set out in our Cookie Notice.',
                'We may share information with Google Analytics to track and analyse use of the Services. To opt out of Google Analytics tracking, visit https://tools.google.com/dlpage/gaoptout. You can also opt out of Google Analytics advertising features through Ads Settings and mobile app settings. Other opt-out means include http://optout.networkadvertising.org/ and http://www.networkadvertising.org/mobile-choice.',
            ],
        },
        {
            title: '6. How long do we keep your information?',
            paragraphs: [
                'We keep personal information only for as long as necessary for the purposes set out in this Privacy Notice, unless a longer period is required or permitted by law, such as for tax, accounting, or other legal requirements.',
                'When there is no ongoing legitimate business need to process personal information, we will delete or anonymise it. If that is not possible, for example because it is stored in backup archives, we will securely store it and isolate it from further processing until deletion is possible.',
            ],
        },
        {
            title: '7. How do we keep your information safe?',
            paragraphs: [
                'We have implemented appropriate and reasonable technical and organisational security measures designed to protect the personal information we process. However, no electronic transmission over the Internet or information storage technology can be guaranteed to be completely secure. Transmission of personal information to and from our Services is at your own risk, and you should access the Services only within a secure environment.',
            ],
        },
        {
            title: '8. Do we collect information from minors?',
            paragraphs: [
                'We do not knowingly collect, solicit data from, or market to children under 18 years of age, nor do we knowingly sell their personal information. If we learn that personal information from a user under 18 has been collected, we will deactivate the account and take reasonable measures to promptly delete the data. If you become aware of data we may have collected from children under 18, contact contact@ukapi.co.uk.',
            ],
        },
        {
            title: '9. What are your privacy rights?',
            paragraphs: [
                'In some regions, including the EEA, UK, and Switzerland, applicable data protection laws may give you rights to access and obtain a copy of your personal information, request rectification or erasure, restrict processing, request data portability, and object to processing or automated decision-making in certain circumstances. You can make a request using the contact details in the section “How can you contact us about this notice?” below.',
                'We will consider and act upon requests in accordance with applicable data protection laws.',
                'If you are in the UK and unhappy with how we have handled your personal information, you can complain directly to us. You may also complain to the Information Commissioner’s Office at ico.org.uk/make-a-complaint, call 0303 123 1113, or write to the Information Commissioner’s Office, Wycliffe House, Water Lane, Wilmslow, Cheshire, SK9 5AF.',
                'You may withdraw consent at any time by contacting us using the details below or updating your preferences. This does not affect the lawfulness of processing before withdrawal or processing based on another lawful ground.',
                'You can unsubscribe from marketing and promotional communications using the unsubscribe link in our emails or by contacting us. We may still send service-related and other non-marketing messages necessary to administer your account or respond to service requests.',
                'Upon a request to terminate an account, we will deactivate or delete the account and information from active databases. We may retain some information to prevent fraud, troubleshoot problems, assist investigations, enforce legal terms, or comply with legal requirements.',
            ],
        },
        {
            title: '10. Controls for do-not-track features',
            paragraphs: [
                'Most browsers and some mobile operating systems and applications include a Do-Not-Track feature or setting. No uniform technology standard for recognising and implementing DNT signals has been finalised, so we do not currently respond to DNT browser signals or other mechanisms that automatically communicate a choice not to be tracked online.',
            ],
        },
        {
            title: '11. Do we make updates to this notice?',
            paragraphs: [
                'We may update this Privacy Notice from time to time. The updated version will be indicated by an updated revised date at the top of the notice. If we make material changes, we may notify you by prominently posting a notice or directly sending you a notification. We encourage you to review this Privacy Notice frequently.',
            ],
        },
        {
            title: '12. How can you contact us about this notice?',
            paragraphs: [
                'If you have questions or comments about this notice, email contact@ukapi.co.uk or contact us by post at UKAPI, United Kingdom.',
            ],
        },
        {
            title: '13. How can you review, update, or delete the data we collect from you?',
            paragraphs: [
                'Depending on the applicable laws of your country, you may have the right to request access to the personal information we collect, details about how we process it, correction of inaccuracies, or deletion. You may also have the right to withdraw consent. These rights may be limited in some circumstances by applicable law.',
                'To review, update, or delete your personal information, submit a data subject access request through the request link provided by UKAPI or contact us using the details above.',
            ],
        },
    ],
};

export default privacyPolicy;
