# **Multi-Company Implementation**
## **Aim:**
Scale securely and efficiently toward a true multi-tenant SaaS model, where user and data management is robust, meeting both operational and regulatory needs.
## **1. Current developments: Tenant and Google Calendar**
**Tenant** approach , which already allows the system to be structured for multiple companies, isolating data and relationships for clients, contacts, and users. **Google Calendar** integration supports the synchronization of events/appointments by company and user, adding value to operational workflow and work organization.
## **2. Current challenges in multi-company management**
The key challenge is to enable:

- **Registered users** are always linked to a single company.
- **Only the general manager** can be associated with (or manage) multiple companies.
- Each user has access only to the data of the company to which they belong, "their" company.
- The registration, invitation, and assignment processes are carried out securely and automatically.
## **3. Phases**
## **A. Association of users to companies in the registry**
- Modify the registration flow to require (or infer via invitation/code) which company the user is associated with.
- Store it as owner_company_id or similar in the users/CrmUsers table.
- Validate at every login and every query that the user only sees information about their company, except for the general administrator.
## **B. Specific role of "General Administrator"**
- Define a special role "SuperAdmin" .
- This user can view and manage multiple companies, create new OwnerCompanies, and manage users associated with each one.
- "Company Admin" type users only manage one company.
## **C. Invitation and user management**
- Implement an invitation system so that administrators of each company can invite users (invited users are automatically associated with the corresponding company).
- Open registration should limit company creation except for the general manager.
## **D. Isolation and access rules at the application layer**
- All Eloquent queries and controllers must filter data by owner_company_id .
- Middleware must deny any attempt at cross-company data access.
## **E. Improvements in multi-company administration**
- The general manager must have a company selection and business management panel.
- Allow switching company context without logging out, for general administrators only.
- Audit: Record which users (and from which company) create, edit, or delete data.
## **F. Google Calendar and multi-company**
- When creating events linked to Google Calendar, be sure to correctly associate them with the user's company.
- Allow selection from multiple calendars (one per company) for users with access to more than one.
## **4. Proposed scheme for user and business management**

|**User**|**Allows access to**|**You can manage users of**|**Visible companies**|
| :-: | :-: | :-: | :-: |
|SuperAdmin|All companies|All|All|
|Company Admin|Your own company|Only users of your company|Just one|
|Standard user|Your own company|-|Just one|
## **5. References & good practices**
- New user registration always requires administrative validation or a company code to avoid "orphans" or incorrectly associated users.
- Strict isolation guaranteed by the direct user-company relationship is essential for multi-company security and privacy.
- Invitations and role management allow for scalability and easy maintenance as businesses grow within the system.



