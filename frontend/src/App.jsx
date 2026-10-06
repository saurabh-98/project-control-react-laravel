import React, { useEffect, useState } from 'react';

import {

    Routes,

    Route,

    Navigate,

    Link,

    useNavigate,

    useLocation

} from 'react-router-dom';

import api from './api';
import './master-data.css';
import './user-role-admin.css';



const getStoredUser = () => {

    try {

        return JSON.parse(localStorage.getItem('pc_user') || '{}');

    } catch {

        return {};

    }

};



const hasPermission = (permission) => {

    const user = getStoredUser();

    if (user.role === 'admin') return true;

    return Array.isArray(user.permissions) && user.permissions.includes(permission);

};



const roleLabel = (role) => {
    if (role && typeof role === 'object') {
        return (
            role.role_label ||
            role.label ||
            role.title ||
            roleLabel(role.role)
        );
    }

    return ({
        admin: 'Administrator',
        project_manager: 'Project Manager',
        engineer: 'Site Engineer',
        qs: 'QS Team',
        viewer: 'Read Only User',
    }[role] || role || 'User');
};

const formatRoleTitle = (role) =>
    String(role || '')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

const fallbackRoleProfile = (role) => {
    const normalized = String(role || '').toLowerCase();

    const defaults = {
        admin: {
            title: 'Administrator',
            short: 'Admin',
            description: 'Full system access',
            access: 'All modules',
            icon: 'A',
        },
        project_manager: {
            title: 'Project Manager',
            short: 'Manager',
            description: 'Configuration & planning',
            access: 'Plans + Configuration',
            icon: 'PM',
        },
        engineer: {
            title: 'Site Engineer',
            short: 'Engineer',
            description: 'Site planning & execution',
            access: 'Plans + Configuration',
            icon: 'SE',
        },
        qs: {
            title: 'QS Team',
            short: 'QS',
            description: 'Quantity & configuration',
            access: 'Configuration + Plans',
            icon: 'QS',
        },
        viewer: {
            title: 'Read Only User',
            short: 'Viewer',
            description: 'View project information',
            access: 'Read only',
            icon: 'RO',
        },
    };

    if (defaults[normalized]) {
        return {
            role: normalized,
            ...defaults[normalized],
        };
    }

    const title = formatRoleTitle(role);

    const short =
        title
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0))
            .join('')
            .toUpperCase() || 'U';

    return {
        role,
        title: title || 'User',
        short,
        description: 'System user',
        access: 'Assigned permissions',
        icon: short,
    };
};

function PermissionRoute({ permission, children }) {

    if (!localStorage.getItem('pc_token')) {

        return <Navigate to="/login" replace />;

    }



    if (!hasPermission(permission)) {

        return <Navigate to="/forbidden" replace />;

    }



    return <Layout>{children}</Layout>;

}



function Forbidden() {

    const nav = useNavigate();

    return (

        <div className="login">

            <div className="login-card forbidden-card">

                <div className="brand big">PROJECT<span>CONTROL</span></div>

                <h2>Access denied</h2>

                <p className="muted">Your role does not have permission to access this page.</p>

                <button className="primary full" onClick={() => nav('/configuration')}>Go to available module</button>

            </div>

        </div>

    );

}





function Layout({ children }) {

    const nav = useNavigate();

    const loc = useLocation();

    const user = getStoredUser();



    return (

        <div className="app">

            <aside>

                <div className="brand">SOBHA</div>

                <nav>

                    {hasPermission('configuration.view') && (

                        <Link className={loc.pathname.includes('configuration') ? 'active' : ''} to="/configuration">

                            Project Configuration

                        </Link>

                    )}

                    {hasPermission('plan.view') && (

                        <Link className={loc.pathname.includes('control') ? 'active' : ''} to="/control">

                            Daily Target V/S Plan

                        </Link>

                    )}

                    {hasPermission('master.manage') && (
                        <Link className={loc.pathname.includes('admin/master-data') ? 'active' : ''} to="/admin/master-data">
                            Master Data
                        </Link>
                    )}
                    {hasPermission('user.manage') && (
                        <Link className={loc.pathname.includes('admin/users-roles') ? 'active' : ''} to="/admin/users-roles">
                            User &amp; Roles
                        </Link>
                    )}

                </nav>



                <div className="role-card">

                    <strong>{user.name || 'User'}</strong>

                    <span>{roleLabel(user)}</span>

                </div>



                <button className="logout" onClick={async () => {

                    try { await api.post('/logout'); } catch {}

                    localStorage.removeItem('pc_token');

                    localStorage.removeItem('pc_user');

                    nav('/login');

                }}>

                    Logout

                </button>

            </aside>



            <main>

                <header>

                    <div>

                        <div className="eyebrow">CONSTRUCTION PLANNING</div>

                        <h1>

                            {loc.pathname.includes('configuration')

                                ? 'Project Configuration'

                                : loc.pathname.includes('control')

                                    ? 'Daily Target V/S Plan'

                                    : 'Access'}

                        </h1>

                    </div>

                    <div className="user">

                        {user.name || 'User'} · {roleLabel(user)}

                    </div>

                </header>

                {children}

            </main>

        </div>

    );

}



function Login() {
    const nav = useNavigate();

    const [roles, setRoles] = useState([]);
    const [selectedRole, setSelectedRole] = useState(null);

    const [f, setF] = useState({
        email: '',
        password: 'password',
    });

    const [err, setErr] = useState('');
    const [loading, setLoading] = useState(false);
    const [rolesLoading, setRolesLoading] = useState(true);
    const [showPassword, setShowPassword] = useState(false);
    const [focused, setFocused] = useState('');

    useEffect(() => {
        let cancelled = false;

        const loadRoles = async () => {
            try {
                setRolesLoading(true);
                setErr('');

                const response = await api.get('/login/roles');

                const rawRoles = Array.isArray(response.data)
                    ? response.data
                    : Array.isArray(response.data?.data)
                        ? response.data.data
                        : [];

                const normalizedRoles = rawRoles
                    .map((item) => {
                        const code =
                            typeof item === 'string'
                                ? item
                                : item?.code || item?.role;

                        if (!code) return null;

                        const fallback =
                            fallbackRoleProfile(code);

                        const label =
                            typeof item === 'object'
                                ? item?.label ||
                                  item?.title ||
                                  fallback.title
                                : fallback.title;

                        return {
                            ...fallback,
                            ...(typeof item === 'object'
                                ? item
                                : {}),
                            code,
                            role: code,
                            label,
                            title: label,
                            short:
                                item?.short ||
                                fallback.short,
                            description:
                                item?.description ||
                                fallback.description,
                            access:
                                item?.access ||
                                fallback.access,
                            icon:
                                item?.icon ||
                                fallback.icon,
                            email:
                                item?.email || '',
                        };
                    })
                    .filter(Boolean);

                if (cancelled) return;

                setRoles(normalizedRoles);

                if (normalizedRoles.length > 0) {
                    const first = normalizedRoles[0];

                    setSelectedRole(first);

                    setF((previous) => ({
                        ...previous,
                        email: first.email || '',
                    }));
                }
            } catch (error) {
                if (!cancelled) {
                    console.error(
                        'Failed to load login roles:',
                        error
                    );

                    setRoles([]);
                    setSelectedRole(null);

                    setErr(
                        error.response?.data?.message ||
                        'Unable to load available login roles.'
                    );
                }
            } finally {
                if (!cancelled) {
                    setRolesLoading(false);
                }
            }
        };

        loadRoles();

        return () => {
            cancelled = true;
        };
    }, []);

    const submit = async (e) => {
        e.preventDefault();
        setErr('');

        const email = f.email.trim();
        const password = f.password;

        if (!email) {
            setErr('Please enter your email address.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setErr('Please enter a valid email address.');
            return;
        }

        if (!password) {
            setErr('Please enter your password.');
            return;
        }

        try {
            setLoading(true);

            const response = await api.post('/login', {
                email,
                password,
            });

            localStorage.setItem(
                'pc_token',
                response.data.token
            );

            const loggedInUser = {
                ...response.data.user,
                role_label:
                    selectedRole?.label ||
                    selectedRole?.title ||
                    response.data.user?.role,
            };

            localStorage.setItem(
                'pc_user',
                JSON.stringify(loggedInUser)
            );

            const permissions =
                response.data.user?.permissions || [];

            if (
                permissions.includes('configuration.view') ||
                response.data.user?.role === 'admin'
            ) {
                nav('/configuration', {
                    replace: true,
                });
            } else if (
                permissions.includes('plan.view')
            ) {
                nav('/control', {
                    replace: true,
                });
            } else {
                nav('/forbidden', {
                    replace: true,
                });
            }
        } catch (error) {
            setErr(
                error.response?.data?.message ||
                error.response?.data?.errors?.email?.[0] ||
                'Invalid email or password. Please try again.'
            );
        } finally {
            setLoading(false);
        }
    };

    const selectRole = (profile) => {
        setSelectedRole(profile);

        setF((previous) => ({
            ...previous,
            email: profile.email || '',
        }));

        setErr('');
    };

    return (
        <div className="login-page">
            <div className="login-background">
                <div className="login-glow glow-one"></div>
                <div className="login-glow glow-two"></div>
            </div>

            <div className="login-shell">
                <div className="login-brand-panel">
                    <div className="brand big">
                        PROJECT<span>CONTROL</span>
                    </div>

                    <div className="brand-content">
                        <div className="brand-badge">
                            CONSTRUCTION PLANNING
                        </div>

                        <h1>
                            Plan smarter.
                            <br />
                            Build better.
                        </h1>

                        <p>
                            Manage project configuration, daily targets,
                            manpower and planned quantities from one
                            centralized workspace.
                        </p>

                        <div className="login-features">
                            <div className="login-feature">
                                <div className="feature-icon">✓</div>
                                <div>
                                    <strong>
                                        Project Configuration
                                    </strong>
                                    <span>
                                        Manage quantities, UOM and priorities
                                    </span>
                                </div>
                            </div>

                            <div className="login-feature">
                                <div className="feature-icon">✓</div>
                                <div>
                                    <strong>Daily Planning</strong>
                                    <span>
                                        Track target versus planned work
                                    </span>
                                </div>
                            </div>

                            <div className="login-feature">
                                <div className="feature-icon">✓</div>
                                <div>
                                    <strong>Role Based Access</strong>
                                    <span>
                                        Secure access based on user roles
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="login-footer">
                        © {new Date().getFullYear()} Project Control
                    </div>
                </div>

                <div className="login-form-panel">
                    <form
                        className="login-card interactive-login"
                        onSubmit={submit}
                    >
                        <div className="mobile-brand">
                            <div className="brand big">
                                PROJECT<span>CONTROL</span>
                            </div>
                        </div>

                        <div className="login-heading">
                            <div className="login-icon">→</div>
                            <div>
                                <h2>Welcome back</h2>
                                <p>
                                    Sign in to continue to your workspace
                                </p>
                            </div>
                        </div>

                        {err && (
                            <div
                                className="login-error"
                                role="alert"
                            >
                                <span className="error-icon">!</span>
                                <span>{err}</span>
                            </div>
                        )}

                        <label
                            className={`login-field ${
                                focused === 'email' || f.email
                                    ? 'active'
                                    : ''
                            }`}
                        >
                            <span className="field-label">
                                Email address
                            </span>

                            <div className="field-control">
                                <span className="field-icon">@</span>

                                <input
                                    type="email"
                                    placeholder="Enter your email"
                                    value={f.email}
                                    autoComplete="email"
                                    disabled={loading}
                                    onFocus={() =>
                                        setFocused('email')
                                    }
                                    onBlur={() =>
                                        setFocused('')
                                    }
                                    onChange={(e) => {
                                        setF({
                                            ...f,
                                            email: e.target.value,
                                        });
                                        setErr('');
                                    }}
                                />

                                {f.email && (
                                    <span className="field-valid">
                                        ✓
                                    </span>
                                )}
                            </div>
                        </label>

                        <label
                            className={`login-field ${
                                focused === 'password' || f.password
                                    ? 'active'
                                    : ''
                            }`}
                        >
                            <div className="password-label-row">
                                <span className="field-label">
                                    Password
                                </span>

                                <button
                                    type="button"
                                    className="forgot-button"
                                    onClick={() =>
                                        setErr(
                                            'Please contact your administrator to reset your password.'
                                        )
                                    }
                                >
                                    Forgot password?
                                </button>
                            </div>

                            <div className="field-control">
                                <span className="field-icon">
                                    •••
                                </span>

                                <input
                                    type={
                                        showPassword
                                            ? 'text'
                                            : 'password'
                                    }
                                    placeholder="Enter your password"
                                    value={f.password}
                                    autoComplete="current-password"
                                    disabled={loading}
                                    onFocus={() =>
                                        setFocused('password')
                                    }
                                    onBlur={() =>
                                        setFocused('')
                                    }
                                    onChange={(e) => {
                                        setF({
                                            ...f,
                                            password: e.target.value,
                                        });
                                        setErr('');
                                    }}
                                />

                                <button
                                    type="button"
                                    className="password-toggle"
                                    onClick={() =>
                                        setShowPassword(
                                            (value) => !value
                                        )
                                    }
                                    aria-label={
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    }
                                >
                                    {showPassword ? '◉' : '○'}
                                </button>
                            </div>
                        </label>

                        <div className="login-options">
                            <label className="remember">
                                <input type="checkbox" />
                                <span>Remember me</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            className={`login-submit ${
                                loading ? 'loading' : ''
                            }`}
                            disabled={
                                loading ||
                                rolesLoading ||
                                !f.email
                            }
                        >
                            {loading ? (
                                <>
                                    <span className="spinner"></span>
                                    Signing in...
                                </>
                            ) : (
                                <>
                                    Sign In
                                    <span className="submit-arrow">
                                        →
                                    </span>
                                </>
                            )}
                        </button>

                        <div className="demo-section">
                            <div className="demo-divider">
                                <span>Sign in as</span>
                            </div>

                            {rolesLoading ? (
                                <div className="role-login-loading">
                                    Loading available roles...
                                </div>
                            ) : roles.length === 0 ? (
                                <div className="role-login-empty">
                                    No login roles are currently available.
                                </div>
                            ) : (
                                <div className="role-login-grid">
                                    {roles.map((profile) => {
                                        const selected =
                                            (selectedRole?.code ||
                                                selectedRole?.role) ===
                                            (profile.code ||
                                                profile.role);

                                        return (
                                            <button
                                                type="button"
                                                key={profile.role}
                                                className={`role-login-card ${
                                                    selected
                                                        ? 'selected'
                                                        : ''
                                                }`}
                                                onClick={() =>
                                                    selectRole(profile)
                                                }
                                                disabled={loading}
                                            >
                                                <span className="role-login-icon">
                                                    {profile.icon}
                                                </span>

                                                <span className="role-login-copy">
                                                    <strong>
                                                        {profile.title}
                                                    </strong>

                                                    <small>
                                                        {
                                                            profile.description
                                                        }
                                                    </small>

                                                    <em>
                                                        {profile.access}
                                                    </em>
                                                </span>

                                                {selected && (
                                                    <span className="role-login-check">
                                                        ✓
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}

                            <div className="selected-role-info">
                                <span>Selected role</span>
                                <strong>
                                     {selectedRole?.label ||
                                        selectedRole?.title ||
                                        'Select a role'}
                                </strong>
                            </div>

                            <p className="demo-password">
                                Demo password:
                                <strong> password</strong>
                            </p>
                        </div>

                        <div className="secure-login">
                            <span>🔒</span>
                            Secure role-based authentication
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

function Filters({ onChange, planDate, setPlanDate, configOnly = false }) {

    const [projects, setProjects] = useState([]);

    const [divs, setDivs] = useState([]);

    const [subs, setSubs] = useState([]);

    const [towers, setTowers] = useState([]);

    const [levels, setLevels] = useState([]);



    const [v, setV] = useState({

        project_id: '',

        division_id: '',

        sub_division_id: '',

        tower_id: '',

        level_id: ''

    });



    const normalizeList = (response) => {
        const payload = response?.data;

        if (Array.isArray(payload)) {
            return payload;
        }

        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        return [];
    };

    const loadAllFilterData = async () => {
        try {
            const [
                projectsResponse,
                divisionsResponse,
                subDivisionsResponse,
                towersResponse,
                levelsResponse
            ] = await Promise.all([
                api.get('/projects'),
                api.get('/divisions'),
                api.get('/sub-divisions'),
                api.get('/towers'),
                api.get('/levels')
            ]);

            const projectList = normalizeList(projectsResponse);
            const divisionList = normalizeList(divisionsResponse);
            const subDivisionList = normalizeList(subDivisionsResponse);
            const towerList = normalizeList(towersResponse);
            const levelList = normalizeList(levelsResponse);

            setProjects(projectList);

            // Project Control: select the first project automatically.
            // Empty child filters mean ALL records for that project.
            if (!configOnly && !v.project_id && projectList.length > 0) {
                const firstProject = projectList[0];
                const initialFilters = {
                    project_id: String(firstProject.id),
                    division_id: '',
                    sub_division_id: '',
                    tower_id: '',
                    level_id: ''
                };

                setV(initialFilters);
                onChange(initialFilters);

                try {
                    const divisionResponse = await api.get(
                        `/projects/${firstProject.id}/divisions`
                    );
                    setDivs(normalizeList(divisionResponse));
                } catch (error) {
                    console.error('Failed to load divisions for default project:', error);
                    setDivs([]);
                }

                setSubs([]);
                setTowers([]);
                setLevels([]);
                return;
            }

            // Configuration: leave filters empty so all records are shown.
            setDivs(divisionList);
            setSubs(subDivisionList);
            setTowers(towerList);
            setLevels(levelList);
        } catch (error) {
            console.error('Failed to load filter master data:', error);
        }
    };

    useEffect(() => {
        loadAllFilterData();
    }, []);



    const set = (key, value) => {

        const next = { ...v, [key]: value };



        if (key === 'project_id') {

            next.division_id = '';

            next.sub_division_id = '';

            next.tower_id = '';

            next.level_id = '';

            setDivs([]);

            setSubs([]);

            setTowers([]);

            setLevels([]);



            if (value) {

                api.get(`/projects/${value}/divisions`)

                    .then((r) => setDivs(r.data || []))

                    .catch(console.error);

            }

        }



        if (key === 'division_id') {

            next.sub_division_id = '';

            next.tower_id = '';

            next.level_id = '';

            setSubs([]);

            setTowers([]);

            setLevels([]);



            if (value) {

                api.get(`/divisions/${value}/sub-divisions`)

                    .then((r) => setSubs(r.data || []))

                    .catch(console.error);

            }

        }



        if (key === 'sub_division_id') {

            next.tower_id = '';

            next.level_id = '';

            setTowers([]);

            setLevels([]);



            if (value) {

                api.get(`/sub-divisions/${value}/towers`)

                    .then((r) => setTowers(r.data || []))

                    .catch(console.error);

            }

        }



        if (key === 'tower_id') {

            next.level_id = '';

            setLevels([]);



            if (value) {

                api.get(`/towers/${value}/levels`)

                    .then((r) => setLevels(r.data || []))

                    .catch(console.error);

            }

        }



        setV(next);

        onChange(next);

    };



    return (

        <div className="filters">

            <Select label="Project" value={v.project_id} onChange={(x) => set('project_id', x)} options={projects} />

            <Select label="Division" value={v.division_id} onChange={(x) => set('division_id', x)} options={divs} />

            <Select label="Sub-Division" value={v.sub_division_id} onChange={(x) => set('sub_division_id', x)} options={subs} />

            <Select label="Tower" value={v.tower_id} onChange={(x) => set('tower_id', x)} options={towers} />

            <Select label="Level" value={v.level_id} onChange={(x) => set('level_id', x)} options={levels} />



            {!configOnly && (

                <label>

                    Date

                    <input type="date" value={planDate} onChange={(e) => setPlanDate(e.target.value)} />

                </label>

            )}

        </div>

    );

}



function Select({ label, value, onChange, options = [] }) {
    const normalizedOptions = Array.isArray(options)
        ? options.filter((item) => item && item.id !== undefined && item.id !== null)
        : [];

    return (
        <label className="filter-select">
            <span className="sr-only">{label}</span>

            <select
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                aria-label={label}
                title={label}
            >
                {/* Default closed state shows the filter name */}
                <option value="">{label}</option>

                {/* Open state shows real values loaded from Laravel */}
                {normalizedOptions.map((item) => (
                    <option key={item.id} value={item.id}>
                        {item.name || item.label || item.code || `#${item.id}`}
                        {item.code && item.name ? ` (${item.code})` : ''}
                    </option>
                ))}
            </select>
        </label>
    );
}



function Configuration() {

    const canEdit = hasPermission('configuration.edit');

    const canImport = hasPermission('configuration.import');

    const canExport = hasPermission('configuration.export');

    const [filters, setFilters] = useState({});

    const [data, setData] = useState([]);

    const [dirty, setDirty] = useState({});

    const [uoms, setUoms] = useState([]);
    const [priorities, setPriorities] = useState([]);
    const [typologies, setTypologies] = useState([]);

    const [msg, setMsg] = useState('');

    const [error, setError] = useState('');

    const [loading, setLoading] = useState(false);

    const [uomLoading, setUomLoading] = useState(false);



    const load = async () => {

        try {

            setLoading(true);

            const r = await api.get('/configurations', {

                params: { ...filters, per_page: 100 }

            });

            setData(r.data?.data || []);

        } catch (e) {

            console.error(e);

            setError(e.response?.data?.message || 'Failed to load project configurations.');

        } finally {

            setLoading(false);

        }

    };



    
    const loadPriorities = async () => {
        try {
            const r = await api.get('/priorities');
            const list = Array.isArray(r.data) ? r.data : (r.data?.data || []);

            setPriorities(
                list.filter((p) => p && p.id !== undefined && p.id !== null)
            );
        } catch (e) {
            console.error(e);
        }
    };

    const loadTypologies = async () => {
        try {
            const r = await api.get('/typologies');
            const list = Array.isArray(r.data) ? r.data : (r.data?.data || []);

            setTypologies(
                list
                    .filter((t) => t && t.id !== undefined && t.id !== null)
                    .map((t) => ({
                        id: Number(t.id),
                        name: t.name || '',
                        code: t.code || '',
                    }))
            );
        } catch (e) {
            console.error(e);
        }
    };

    const loadUoms = async () => {

        try {

            setUomLoading(true);

            const r = await api.get('/uoms');

            const list = Array.isArray(r.data) ? r.data : (r.data?.data || []);



            setUoms(

                list

                    .filter((u) => u && u.id !== undefined && u.id !== null)

                    .map((u) => ({ id: Number(u.id), name: u.name || '' }))

            );

        } catch (e) {

            console.error(e);

            setError(e.response?.data?.message || 'Failed to load UOM list.');

        } finally {

            setUomLoading(false);

        }

    };



    useEffect(() => { loadUoms(); loadPriorities(); loadTypologies(); }, []);

    useEffect(() => { load(); }, [JSON.stringify(filters)]);



    const edit = (id, key, value) => {

        setDirty((prev) => ({

            ...prev,

            [id]: { ...(prev[id] || {}), [key]: value }

        }));

        setMsg('');

        setError('');

    };



    const save = async () => {

        try {

            setError('');

            setMsg('');



            const rows = Object.entries(dirty).map(([id, values]) => {

                const row = { id: Number(id) };



                if (values.quantity !== undefined) row.quantity = Number(values.quantity);

                if (values.uom_id !== undefined) row.uom_id = Number(values.uom_id);

                if (values.priority !== undefined) row.priority = Number(values.priority);



                if (values.typology_id !== undefined) {

                    row.typology_id = values.typology_id

                        ? Number(values.typology_id)

                        : null;

                }



                return row;

            });



            if (!rows.length) {

                setMsg('No changes to save.');

                return;

            }



            for (const row of rows) {

                if (

                    row.uom_id !== undefined &&

                    !uoms.some((uom) => uom.id === row.uom_id)

                ) {

                    throw new Error(`Invalid UOM selected for configuration ${row.id}.`);

                }

            }



            await api.post('/configurations/bulk-update', { rows });



            setDirty({});

            setMsg('Configuration saved successfully.');

            await load();

        } catch (e) {

            console.error(e);

            setError(

                e.response?.data?.message ||

                e.message ||

                'Failed to save configuration.'

            );

        }

    };



    const exportCsv = async () => {

        try {

            const r = await api.get('/configurations/export', {

                params: filters,

                responseType: 'blob'

            });



            const url = URL.createObjectURL(r.data);

            const a = document.createElement('a');

            a.href = url;

            a.download = 'project-configuration.csv';

            document.body.appendChild(a);

            a.click();

            a.remove();

            URL.revokeObjectURL(url);

        } catch (e) {

            console.error(e);

            setError('Failed to export configuration.');

        }

    };



    const importCsv = async (e) => {

        const file = e.target.files?.[0];

        if (!file) return;



        try {

            setError('');

            setMsg('');



            const fd = new FormData();

            fd.append('file', file);



            const r = await api.post('/configurations/import', fd, {

                headers: { 'Content-Type': 'multipart/form-data' }

            });



            setMsg(r.data?.message || 'Configuration imported successfully.');

            await load();

        } catch (error) {

            console.error(error);

            setError(

                error.response?.data?.message ||

                'Failed to import configuration.'

            );

        }



        e.target.value = '';

    };



    return (

        <section>

            <div className="toolbar">

                <div>

                    <strong>Project Configuration</strong>

                  

                </div>



                <div className="actions config-actions">
                    {canEdit && (
                        <button
                            type="button"
                            className="config-action-button"
                            onClick={save}
                            disabled={loading || Object.keys(dirty).length === 0}
                        >
                            Save
                        </button>
                    )}

                    {canExport && (
                        <button
                            type="button"
                            className="config-action-button"
                            onClick={exportCsv}
                        >
                            Export Config
                        </button>
                    )}

                    {canImport && (
                        <label className="upload config-action-button">
                            <span>Bulk Upload</span>
                            <span className="upload-icon" aria-hidden="true"><span className="upload-arrow">↓</span><span className="upload-tray"></span></span>
                            <input
                                type="file"
                                accept=".csv,.txt"
                                onChange={importCsv}
                                hidden
                            />
                        </label>
                    )}
                </div>

            </div>



            <Filters configOnly onChange={setFilters} />



            {msg && <div className="success">{msg}</div>}

            {error && <div className="error">{error}</div>}



            <div className="tablewrap">

                <table>

                    <thead>

                        <tr>

                            <th>S. No</th>

                            <th>Sub-Activity Code</th>

                            <th>Activity Name</th>

                            <th>Sub-Activity Name</th>

                            <th>Unit</th>

                            <th>Apartment Typology</th>

                            <th>Quantity</th>

                            <th>UOM</th>

                            <th>Priority</th>

                        </tr>

                    </thead>



                    <tbody>

                        {loading ? (

                            <tr>

                                <td colSpan="9" style={{ textAlign: 'center' }}>

                                    Loading...

                                </td>

                            </tr>

                        ) : data.length === 0 ? (

                            <tr>

                                <td colSpan="9" style={{ textAlign: 'center' }}>

                                    No configuration records found.

                                </td>

                            </tr>

                        ) : (

                            data.map((r, index) => {

                                const uomId =

                                    dirty[r.id]?.uom_id ??

                                    r.uom_id ??

                                    '';



                                const quantity =

                                    dirty[r.id]?.quantity ??

                                    r.quantity ??

                                    '';



                                const priority =

                                    dirty[r.id]?.priority ??

                                    r.priority ??

                                    1;



                                return (

                                    <tr key={r.id}>

                                        <td className="serial-cell">{index + 1}.</td>

                                        <td>
                                            <strong>{r.sub_activity?.code || '-'}</strong>
                                        </td>

                                        <td>{r.activity?.name || '-'}</td>

                                        <td className="subactivity-cell">
                                            {r.sub_activity?.name || '-'}
                                        </td>

                                        <td>
                                            <span className="unit-value">
                                                {r.apartment?.code || '-'}
                                            </span>
                                        </td>

                                        <td>
                                            <select
                                                className="cell typology-select"
                                                disabled={!canEdit}
                                                value={
                                                    dirty[r.id]?.typology_id ??
                                                    r.typology_id ??
                                                    r.typology?.id ??
                                                    r.apartment?.typology_id ??
                                                    r.apartment?.typology?.id ??
                                                    ''
                                                }
                                                onChange={(e) =>
                                                    edit(
                                                        r.id,
                                                        'typology_id',
                                                        e.target.value
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Select
                                                </option>

                                                {typologies.map((typology) => (
                                                    <option
                                                        key={typology.id}
                                                        value={typology.id}
                                                    >
                                                        {typology.name}
                                                        {typology.code
                                                            ? ` (${typology.code})`
                                                            : ''}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>

                                        <td>
                                            <input
                                                className="cell quantity-cell"
                                                type="number"
                                                min="0"
                                                step="0.001"
                                                disabled={!canEdit}
                                                value={quantity}
                                                onChange={(e) =>
                                                    edit(
                                                        r.id,
                                                        'quantity',
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </td>

                                        <td>
                                            <select
                                                className="cell uom-select"
                                                value={uomId}
                                                disabled={!canEdit || uomLoading}
                                                onChange={(e) =>
                                                    edit(
                                                        r.id,
                                                        'uom_id',
                                                        e.target.value
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Select
                                                </option>

                                                {uoms.map((uom) => (
                                                    <option
                                                        key={uom.id}
                                                        value={uom.id}
                                                    >
                                                        {uom.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>

                                        <td>
                                            <select
                                                className="cell priority-select"
                                                disabled={!canEdit}
                                                value={priority}
                                                onChange={(e) =>
                                                    edit(
                                                        r.id,
                                                        'priority',
                                                        e.target.value
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Select
                                                </option>

                                                {priorities.map((item) => (
                                                    <option
                                                        key={item.id}
                                                        value={item.value}
                                                    >
                                                        {item.label ||
                                                            `Priority ${item.value}`}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>

                                    </tr>

                                );

                            })

                        )}

                    </tbody>

                </table>

            </div>

        </section>

    );

}



function Control() {
    const canSavePlan = hasPermission('plan.create');
    const canSubmitPlan = hasPermission('plan.submit');

    const [filters, setFilters] = useState({});
    const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
    const [items, setItems] = useState([]);
    const [open, setOpen] = useState({});
    const [rows, setRows] = useState({});
    const [plan, setPlan] = useState(null);
    const [msg, setMsg] = useState('');
    const [search, setSearch] = useState('');

    const formatDate = (value) => {
        if (!value) return '-';

        // Laravel date-only values should be rendered as date-only.
        // Avoid timezone conversion such as 2025-04-18 -> 17-Apr-2025.
        const raw = String(value).trim();
        const dateOnly = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);

        if (dateOnly) {
            const [, year, month, day] = dateOnly;
            const d = new Date(Number(year), Number(month) - 1, Number(day));

            return d.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return raw;

        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    const number = (value, fallback = 0) => {
        const n = Number(value);
        return Number.isFinite(n) ? n : fallback;
    };

    const formatQty = (value) => {
        const n = number(value);
        return Number.isInteger(n) ? String(n) : n.toFixed(2);
    };

    const build = async () => {
        if (!filters.project_id) {
            setItems([]);
            setPlan(null);
            setRows({});
            setOpen({});
            return;
        }

        try {
            const r = await api.post('/plans/build', {
                project_id: Number(filters.project_id),
                plan_date: date,
                filters: {
                    project_id: Number(filters.project_id),
                    division_id: filters.division_id || '',
                    sub_division_id: filters.sub_division_id || '',
                    tower_id: filters.tower_id || '',
                    level_id: filters.level_id || ''
                }
            });

            const nextItems = r.data.items || [];
            setPlan(r.data.plan || null);
            setItems(nextItems);

            const initial = {};
            nextItems.forEach((g) => {
                (g.apartments || []).forEach((a) => {
                    initial[`${g.sub_activity.id}_${a.apartment.id}`] = {
                        ...a,
                        sub_activity_id: g.sub_activity.id
                    };
                });
            });
            setRows(initial);

            // All activity groups are expanded by default.
            const allOpen = {};
            nextItems.forEach((g) => {
                if (g.sub_activity?.id) {
                    allOpen[g.sub_activity.id] = true;
                }
            });
            setOpen(allOpen);
        } catch (e) {
            console.error('Failed to build plan:', e);
            setItems([]);
            setRows({});
            setPlan(null);
            setOpen({});
        }
    };

    useEffect(() => {
        build();
    }, [JSON.stringify(filters), date]);

    const update = (key, field, value) => {
        setRows((prev) => ({
            ...prev,
            [key]: { ...prev[key], [field]: value }
        }));
    };

    const plannedFor = (g) =>
        (g.apartments || []).reduce((sum, a) => {
            const key = `${g.sub_activity.id}_${a.apartment.id}`;
            const x = rows[key] || a;
            return sum + number(x.planned_quantity);
        }, 0);

    const save = async (submit = false) => {
        if (!plan) return;

        const payload = Object.values(rows).map((x) => ({
            sub_activity_id: x.sub_activity_id || 0,
            apartment_id: x.apartment.id,
            planned_quantity: number(x.planned_quantity),
            completion_quantity: number(x.completion_quantity),
            planned_manpower: number(x.planned_manpower),
            reason_id: x.reason_id ? Number(x.reason_id) : null,
            reason_text: x.reason_text || null,
            working_days: 8
        }));

        try {
            const r = await api.post('/plans/save', {
                project_id: plan.project_id,
                plan_date: date,
                rows: payload
            });

            setPlan(r.data.plan);
            setMsg('Plan saved successfully.');

            if (submit) {
                await api.post(`/plans/${r.data.plan.id}/submit`);
                setMsg('Plan submitted successfully.');
                await build();
            }
        } catch (e) {
            console.error('Failed to save plan:', e);
        }
    };

    const filteredItems = items.filter((g) => {
        const q = search.trim().toLowerCase();
        if (!q) return true;
        const groupText = [
            g.activity?.name,
            g.activity?.code,
            g.sub_activity?.name,
            g.sub_activity?.code,
            ...(g.apartments || []).flatMap((a) => [
                a.apartment?.code,
                a.apartment?.name,
                a.apartment?.typology?.name
            ])
        ].filter(Boolean).join(' ').toLowerCase();
        return groupText.includes(q);
    });

    const expandAll = () => {
        setOpen(Object.fromEntries(items.map((g) => [g.sub_activity.id, true])));
    };

    return (
        <section className="control-page">
            <div className="control-topbar">
                <div className="control-title">PROJECT CONTROL</div>
                <div className="control-actions">
                    {canSavePlan && (
                        <button type="button" onClick={() => save(false)}>Save Plan</button>
                    )}
                    {canSubmitPlan && (
                        <button type="button" className="dark-primary" onClick={() => save(true)}>
                            Submit Plan
                        </button>
                    )}
                </div>
            </div>

            <div className="control-filter-card">
                <div className="control-filter-label">Filter By</div>
                <div className="control-filter-fields">
                    <Filters
                        onChange={setFilters}
                        planDate={date}
                        setPlanDate={setDate}
                    />
                    <div className="control-search-wrap">
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder=""
                            aria-label="Search activity or apartment"
                        />
                        <span aria-hidden="true">⌕</span>
                    </div>
                    <button type="button" className="expand-button" onClick={expandAll}>
                        Expand All
                    </button>
                </div>
            </div>

            {msg && <div className="success control-message">{msg}</div>}

            <div className="control-list">
                {filteredItems.length === 0 ? (
                    <div className="control-empty">
                        No project control data found.
                    </div>
                ) : filteredItems.map((g) => {
                    const subId = g.sub_activity.id;
                    const isOpen = !!open[subId];
                    const planned = plannedFor(g);
                    const target = number(g.target_quantity);
                    const shortfall = number(g.shortfall ?? (planned - target));
                    const backlog = number(
                        g.backlog ?? g.cumulative_backlog ?? g.backlog_quantity ?? shortfall
                    );
                    const percent = number(
                        g.backlog_percentage ?? g.target_percentage ?? g.progress_percentage,
                        target > 0 ? (planned / target) * 100 : 0
                    );
                    const activity = g.activity || {};
                    const sub = g.sub_activity || {};
                    const startDate =
                        g.start_date ||
                        g.activity_start_date ||
                        g.sub_activity_start_date ||
                        sub.start_date ||
                        activity.start_date ||
                        g.startDate ||
                        sub.startDate ||
                        activity.startDate;

                    const finishDate =
                        g.finish_date ||
                        g.activity_finish_date ||
                        g.sub_activity_finish_date ||
                        sub.finish_date ||
                        activity.finish_date ||
                        g.finishDate ||
                        sub.finishDate ||
                        activity.finishDate;
                    const totalQuantity = g.total_quantity ?? g.quantity ?? 0;
                    const manDays = g.man_days ?? g.planned_working_days ?? sub.planned_working_days ?? 0;
                    const productivity = g.productivity ?? sub.productivity ?? 0;
                    const manpower = g.planned_manpower ?? g.manpower ?? 0;

                    return (
                        <div className={`control-activity ${isOpen ? 'is-open' : ''}`} key={subId}>
                            <div
                                className="control-activity-head"
                                onClick={() => setOpen((o) => ({ ...o, [subId]: !isOpen }))}
                            >
                                <div className="control-activity-main">
                                    <span className="control-eyebrow">Activity Name</span>
                                    <div className="control-activity-name">
                                        {activity.name || '-'} {activity.code ? `- ${activity.code}` : ''}
                                    </div>
                                    <div className="control-activity-meta">
                                        <span>Total Quantity: <b>{formatQty(totalQuantity)} {g.uom?.name || g.unit || ''}</b></span>
                                        <span>Man Days: <b>{formatQty(manDays)} Days</b></span>
                                    </div>
                                </div>

                                <div className="control-dates">
                                    <span><b>Start Date:</b> {formatDate(startDate)}</span>
                                    <span><b>Finish Date:</b> {formatDate(finishDate)}</span>
                                </div>

                                <span className="control-chevron">{isOpen ? '⌃' : '⌄'}</span>
                            </div>

                            {isOpen && (
                                <div className="control-open-body">
                                    <div className="control-sub-head">
                                        <div className="control-sub-title">
                                            <span className="control-eyebrow">Sub-Activity Name</span>
                                            <div>{sub.name || '-'} {sub.code ? `- ${sub.code}` : ''}</div>
                                            <div className="control-sub-meta">
                                                ASTP Productivity: <b>{formatQty(productivity)}</b>
                                                <span>Planned Manpower: <b>{formatQty(manpower)}</b></span>
                                            </div>
                                            <div className="control-sub-dates">
                                                Start Date: <b>{formatDate(startDate)}</b>
                                                <span>Finish Date: <b>{formatDate(finishDate)}</b></span>
                                            </div>
                                        </div>

                                        <div className="control-metrics">
                                            <div className="control-metric">
                                                <span>Target Qty.</span><small>for the day</small><b>{formatQty(target)} {g.uom?.name || g.unit || 'm²'}</b>
                                            </div>
                                            <div className="control-metric">
                                                <span>Planned Qty.</span><small>for the day</small><b>{formatQty(planned)} {g.uom?.name || g.unit || 'm²'}</b>
                                            </div>
                                            <div className="control-metric">
                                                <span>Short Fall</span><small>Target VS Plan</small><b>{formatQty(shortfall)} {g.uom?.name || g.unit || 'm²'}</b>
                                            </div>
                                            <div className="control-metric">
                                                <span>Backlog</span><small>Cumulative</small><b>{formatQty(backlog)} {g.uom?.name || g.unit || 'm²'}</b>
                                            </div>
                                            <div className="control-progress">{Math.round(percent)}%</div>
                                        </div>
                                    </div>

                                    <div className="control-table-wrap">
                                        <table className="control-table">
                                            <thead>
                                                <tr>
                                                    <th className="drag-col">☷</th>
                                                    <th>Apartment</th>
                                                    <th>Priority</th>
                                                    <th>Required Quantity</th>
                                                    <th>Planned Quantity</th>
                                                    <th>Completion Till Date</th>
                                                    <th>Planned Manpower</th>
                                                    <th>Reason for Gap</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {(g.apartments || []).map((a) => {
                                                    const key = `${subId}_${a.apartment.id}`;
                                                    const x = rows[key] || a;
                                                    const completion = x.completion_quantity ?? a.completion_quantity ?? a.completion_till_date ?? 0;
                                                    return (
                                                        <tr key={key}>
                                                            <td className="drag-col">☷</td>
                                                            <td>{a.apartment?.code || '-'}</td>
                                                            <td>{a.priority ?? '-'}</td>
                                                            <td>{formatQty(a.required_quantity)}</td>
                                                            <td>
                                                                <input className="control-number" type="number" min="0" disabled={!canSavePlan}
                                                                    value={x.planned_quantity ?? 0}
                                                                    onChange={(e) => update(key, 'planned_quantity', e.target.value)} />
                                                            </td>
                                                            <td>{formatQty(completion)} {a.uom?.name || g.uom?.name || ''}</td>
                                                            <td>{formatQty(x.planned_manpower ?? a.planned_manpower ?? 0)}</td>
                                                            <td>
                                                                <input className="control-reason" placeholder="Reason for gap" disabled={!canSavePlan}
                                                                    value={x.reason_text || ''}
                                                                    onChange={(e) => update(key, 'reason_text', e.target.value)} />
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </section>
    );
}


function MasterData() {
    const resources = [
        { key: 'projects', label: 'Projects', group: 'Project Structure' },
        { key: 'divisions', label: 'Divisions', group: 'Project Structure' },
        { key: 'sub-divisions', label: 'Sub-Divisions', group: 'Project Structure' },
        { key: 'towers', label: 'Towers', group: 'Project Structure' },
        { key: 'levels', label: 'Levels', group: 'Project Structure' },
        { key: 'activities', label: 'Activities', group: 'Work Master' },
        { key: 'sub-activities', label: 'Sub-Activities', group: 'Work Master' },
        { key: 'apartments', label: 'Apartments', group: 'Apartment Master' },
        { key: 'typologies', label: 'Apartment Typologies', group: 'Apartment Master' },
        { key: 'uoms', label: 'UOM', group: 'Planning Master' },
        { key: 'priorities', label: 'Priority', group: 'Planning Master' },
        { key: 'reasons', label: 'Gap Reasons', group: 'Planning Master' },
    ];

    const [resource, setResource] = useState('projects');
    const [rows, setRows] = useState([]);
    const [lookups, setLookups] = useState({});
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [editing, setEditing] = useState(null);

    const blank = () => ({
        id: null,
        code: '',
        name: '',
        label: '',
        value: '',
        project_id: '',
        division_id: '',
        sub_division_id: '',
        tower_id: '',
        level_id: '',
        activity_id: '',
        typology_id: '',
        uom_id: '',
        productivity: '',
        sort_order: '',
        planned_working_days: '',
        start_date: '',
        finish_date: '',
        active: true,
        is_active: true,
    });

    const [form, setForm] = useState(blank());

    const loadLookups = async () => {
        try {
            const endpoints = [
                ['projects', '/projects'],
                ['divisions', '/divisions'],
                ['sub_divisions', '/sub-divisions'],
                ['towers', '/towers'],
                ['levels', '/levels'],
                ['activities', '/activities'],
                ['typologies', '/typologies'],
                ['uoms', '/uoms'],
            ];

            const responses = await Promise.all(
                endpoints.map(async ([key, url]) => {
                    const r = await api.get(url);
                    const list = Array.isArray(r.data)
                        ? r.data
                        : (r.data?.data || []);
                    return [key, list];
                })
            );

            setLookups(Object.fromEntries(responses));
        } catch (e) {
            console.error(e);
        }
    };

    const load = async () => {
        try {
            setLoading(true);
            setError('');
            const r = await api.get(`/admin/master-data/${resource}`);
            setRows(r.data.data || []);
        } catch (e) {
            setError(
                e.response?.data?.message ||
                'Unable to load master data.'
            );
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadLookups();
    }, []);

    useEffect(() => {
        setEditing(null);
        setForm(blank());
        load();
    }, [resource]);

    const startCreate = () => {
        setEditing('new');
        setForm(blank());
        setError('');
        setMessage('');
    };

    const startEdit = (row) => {
        setEditing(row.id);
        setForm({
            ...blank(),
            ...row,
            project_id: row.project_id ?? '',
            division_id: row.division_id ?? '',
            sub_division_id: row.sub_division_id ?? '',
            tower_id: row.tower_id ?? '',
            level_id: row.level_id ?? '',
            activity_id: row.activity_id ?? '',
            typology_id: row.typology_id ?? '',
            uom_id: row.uom_id ?? '',
            active: row.active ?? row.is_active ?? true,
            is_active: row.is_active ?? row.active ?? true,
        });
        setError('');
        setMessage('');
    };

    const save = async (e) => {
        e.preventDefault();

        try {
            setSaving(true);
            setError('');
            setMessage('');

            const payload = { ...form };
            delete payload.id;

            if (resource === 'priorities') {
                payload.value = Number(payload.value);
                payload.label = payload.label.trim();
            }

            if (resource !== 'priorities') {
                payload.code = payload.code?.trim();
                payload.name = payload.name?.trim();
            }

            if (editing === 'new') {
                await api.post(`/admin/master-data/${resource}`, payload);
                setMessage(`${resources.find(x => x.key === resource)?.label || 'Master'} created successfully.`);
            } else {
                await api.put(`/admin/master-data/${resource}/${editing}`, payload);
                setMessage(`${resources.find(x => x.key === resource)?.label || 'Master'} updated successfully.`);
            }

            setEditing(null);
            setForm(blank());
            await Promise.all([load(), loadLookups()]);
        } catch (e) {
            const validation = e.response?.data?.errors;
            const firstValidation = validation
                ? Object.values(validation)?.[0]?.[0]
                : null;

            setError(
                firstValidation ||
                e.response?.data?.message ||
                'Unable to save master data.'
            );
        } finally {
            setSaving(false);
        }
    };

    const remove = async (row) => {
        const title = row.name || row.label || row.code || row.value;
        if (!window.confirm(`Delete "${title}"?`)) return;

        try {
            setSaving(true);
            setError('');
            await api.delete(`/admin/master-data/${resource}/${row.id}`);
            setMessage('Deleted successfully.');
            await Promise.all([load(), loadLookups()]);
        } catch (e) {
            setError(
                e.response?.data?.message ||
                'Unable to delete. This record may be used by another record.'
            );
        } finally {
            setSaving(false);
        }
    };

    const options = (key) => lookups[key] || [];

    const fields = {
        projects: [
            ['code', 'Code', 'text', true],
            ['name', 'Project Name', 'text', true],
            ['start_date', 'Start Date', 'date', false],
            ['end_date', 'End Date', 'date', false],
        ],
        divisions: [
            ['project_id', 'Project', 'lookup', true, 'projects'],
            ['code', 'Division Code', 'text', true],
            ['name', 'Division Name', 'text', true],
        ],
        'sub-divisions': [
            ['division_id', 'Division', 'lookup', true, 'divisions'],
            ['code', 'Sub-Division Code', 'text', true],
            ['name', 'Sub-Division Name', 'text', true],
        ],
        towers: [
            ['sub_division_id', 'Sub-Division', 'lookup', true, 'sub_divisions'],
            ['code', 'Tower Code', 'text', true],
            ['name', 'Tower Name', 'text', true],
        ],
        levels: [
            ['tower_id', 'Tower', 'lookup', true, 'towers'],
            ['code', 'Level Code', 'text', true],
            ['name', 'Level Name', 'text', true],
            ['sort_order', 'Sort Order', 'number', false],
        ],
        activities: [
            ['project_id', 'Project', 'lookup', true, 'projects'],
            ['code', 'Activity Code', 'text', true],
            ['name', 'Activity Name', 'text', true],
            ['uom_id', 'Default UOM', 'lookup', false, 'uoms'],
            ['start_date', 'Start Date', 'date', false],
            ['finish_date', 'Finish Date', 'date', false],
            ['planned_working_days', 'Planned Working Days', 'number', false],
        ],
        'sub-activities': [
            ['activity_id', 'Activity', 'lookup', true, 'activities'],
            ['code', 'Sub-Activity Code', 'text', true],
            ['name', 'Sub-Activity Name', 'text', true],
            ['productivity', 'Productivity', 'number', false],
            ['start_date', 'Start Date', 'date', false],
            ['finish_date', 'Finish Date', 'date', false],
            ['planned_working_days', 'Planned Working Days', 'number', false],
        ],
        apartments: [
            ['project_id', 'Project', 'lookup', true, 'projects'],
            ['tower_id', 'Tower', 'lookup', true, 'towers'],
            ['level_id', 'Level', 'lookup', true, 'levels'],
            ['code', 'Apartment Code', 'text', true],
            ['name', 'Apartment Name', 'text', false],
            ['typology_id', 'Apartment Typology', 'lookup', false, 'typologies'],
        ],
        typologies: [
            ['code', 'Typology Code', 'text', true],
            ['name', 'Typology Name', 'text', true],
        ],
        uoms: [
            ['code', 'UOM Code', 'text', true],
            ['name', 'UOM Name', 'text', true],
        ],
        priorities: [
            ['value', 'Priority Number', 'number', true],
            ['label', 'Priority Label', 'text', true],
        ],
        reasons: [
            ['code', 'Reason Code', 'text', true],
            ['name', 'Reason', 'text', true],
        ],
    };

    const currentFields = fields[resource] || [];

    const resourceLabel = resources.find(x => x.key === resource)?.label || 'Record';

    const singularResourceLabel = {
        projects: 'Project',
        divisions: 'Division',
        'sub-divisions': 'Sub-Division',
        towers: 'Tower',
        levels: 'Level',
        activities: 'Activity',
        'sub-activities': 'Sub-Activity',
        apartments: 'Apartment',
        typologies: 'Apartment Typology',
        uoms: 'UOM',
        priorities: 'Priority',
        reasons: 'Gap Reason',
    }[resource] || resourceLabel;

    return (
        <section className="master-page">
            <div className="master-header">
                <div>
                    <div className="eyebrow">ADMINISTRATION / MASTER DATA</div>
                    <h2>Master Data Management</h2>
                    <p className="muted">
                        Administrator creates the master records used by Project Configuration and Project Control.
                    </p>
                </div>
            </div>

            {message && <div className="success">{message}</div>}
            {error && <div className="error">{error}</div>}

            <div className="master-layout">
                <aside className="master-nav">
                    {['Project Structure', 'Work Master', 'Apartment Master', 'Planning Master'].map((group) => {
                        const groupItems = resources.filter((item) => item.group === group);

                        return (
                            <div className="master-nav-group" key={group}>
                                <div className="master-nav-title">{group}</div>

                                {groupItems.map((item) => (
                                    <button
                                        type="button"
                                        key={item.key}
                                        className={resource === item.key ? 'active' : ''}
                                        onClick={() => setResource(item.key)}
                                    >
                                        <span>{item.label}</span>
                                        {resource === item.key && (
                                            <span className="master-nav-current">●</span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        );
                    })}
                </aside>

                <div className="master-content">
                    <div className="master-content-head">
                        <div>
                            <strong>{resourceLabel}</strong>
                            <span>{rows.length} records</span>
                        </div>
                        <div className="master-content-actions">
                            <button type="button" onClick={load} disabled={loading}>Refresh</button>
                            <button type="button" className="primary master-content-add" onClick={startCreate}>
                                + Add {singularResourceLabel}
                            </button>
                        </div>
                    </div>

                    {loading ? (
                        <div className="empty-state">Loading...</div>
                    ) : (
                        <div className="admin-table-wrap">
                            <table className="admin-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code / Value</th>
                                        <th>Name / Label</th>
                                        <th>Parent</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, index) => {
                                        const parentName =
                                            row.parent_name ||
                                            row.project_name ||
                                            row.division_name ||
                                            row.sub_division_name ||
                                            row.tower_name ||
                                            row.activity_name ||
                                            row.typology_name ||
                                            '-';

                                        return (
                                        <tr key={row.id}>
                                            <td>{index + 1}</td>
                                            <td>
                                                <strong>
                                                    {row.code ?? row.value ?? '-'}
                                                </strong>
                                            </td>
                                            <td>{row.name ?? row.label ?? '-'}</td>
                                            <td>{parentName}</td>
                                            <td>
                                                <span className={(row.is_active ?? row.active ?? true) ? 'status-active' : 'status-inactive'}>
                                                    {(row.is_active ?? row.active ?? true) ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td>
                                                <div className="row-actions">
                                                    <button onClick={() => startEdit(row)}>Edit</button>
                                                    <button className="danger" onClick={() => remove(row)} disabled={saving}>
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        );
                                    })}

                                    {!rows.length && (
                                        <tr>
                                            <td colSpan="6" className="empty-cell">
                                                No {resources.find(x => x.key === resource)?.label?.toLowerCase()} found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {editing !== null && (
                <div className="modal-backdrop">
                    <form className="admin-modal wide" onSubmit={save}>
                        <div className="modal-head">
                            <div>
                                <div className="eyebrow">ADMIN MASTER</div>
                                <h3>
                                    {editing === 'new' ? 'Create' : 'Edit'}{' '}
                                    {resources.find(x => x.key === resource)?.label}
                                </h3>
                            </div>
                            <button type="button" className="modal-close" onClick={() => setEditing(null)}>×</button>
                        </div>

                        <div className="master-form-grid">
                            {currentFields.map(([key, label, type, required, lookupKey]) => (
                                <label key={key}>
                                    {label}
                                    {type === 'lookup' ? (
                                        <select
                                            required={required}
                                            value={form[key] ?? ''}
                                            onChange={(e) => setForm({ ...form, [key]: e.target.value })}
                                        >
                                            <option value="">Select {label}</option>
                                            {options(lookupKey).map((item) => (
                                                <option key={item.id} value={item.id}>
                                                    {item.name || item.label || item.code}
                                                    {item.code && item.name ? ` (${item.code})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <input
                                            required={required}
                                            type={type}
                                            min={type === 'number' ? '0' : undefined}
                                            step={type === 'number' ? '0.0001' : undefined}
                                            value={form[key] ?? ''}
                                            onChange={(e) => setForm({ ...form, [key]: e.target.value })}
                                        />
                                    )}
                                </label>
                            ))}
                        </div>

                        {['projects', 'divisions', 'sub-divisions', 'towers', 'levels', 'activities', 'sub-activities', 'apartments', 'typologies', 'uoms', 'reasons'].includes(resource) && (
                            <label className="master-checkbox">
                                <input
                                    type="checkbox"
                                    checked={form.is_active !== false && form.active !== false}
                                    onChange={(e) => setForm({
                                        ...form,
                                        is_active: e.target.checked,
                                        active: e.target.checked,
                                    })}
                                />
                                Active
                            </label>
                        )}

                        <div className="modal-actions">
                            <button type="button" onClick={() => setEditing(null)}>Cancel</button>
                            <button className="primary" disabled={saving}>
                                {saving ? 'Saving...' : 'Save'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </section>
    );
}


function UserRoleAdmin() {
    const [tab, setTab] = useState('users');
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [editingUser, setEditingUser] = useState(null);
    const [editingRole, setEditingRole] = useState(null);

    const emptyUser = {
        name: '',
        email: '',
        password: '',
        role: 'viewer',
        is_active: true,
    };

    const emptyRole = {
        name: '',
        label: '',
        permissions: [],
    };

    const [userForm, setUserForm] = useState(emptyUser);
    const [roleForm, setRoleForm] = useState(emptyRole);

    const permissionGroups = {
        master: [
            ['master.view', 'View master data'],
            ['master.manage', 'Manage master data'],
        ],
        configuration: [
            ['configuration.view', 'View project configuration'],
            ['configuration.edit', 'Edit project configuration'],
            ['configuration.import', 'Import configuration'],
            ['configuration.export', 'Export configuration'],
        ],
        plan: [
            ['plan.view', 'View daily plans'],
            ['plan.create', 'Create and save daily plans'],
            ['plan.submit', 'Submit daily plans'],
        ],
        administration: [
            ['user.manage', 'Manage users'],
            ['role.manage', 'Manage roles and permissions'],
        ],
    };

    const allPermissions = Object.values(permissionGroups).flat().map(([name]) => name);

    const roleOptions = [
        ['admin', 'Administrator'],
        ['project_manager', 'Project Manager'],
        ['engineer', 'Site Engineer'],
        ['qs', 'QS Team'],
        ['viewer', 'Read Only User'],
    ];

    const normalize = (response) => {
        const payload = response?.data;
        if (Array.isArray(payload)) return payload;
        if (Array.isArray(payload?.data)) return payload.data;
        if (Array.isArray(payload?.users)) return payload.users;
        if (Array.isArray(payload?.roles)) return payload.roles;
        return [];
    };

    const getError = (e, fallback) =>
        e?.response?.data?.message ||
        Object.values(e?.response?.data?.errors || {})?.[0]?.[0] ||
        fallback;

    const loadUsers = async () => {
        const r = await api.get('/admin/users');
        setUsers(normalize(r));
    };

    const loadRoles = async () => {
        const r = await api.get('/admin/roles');
        setRoles(normalize(r));
    };

    const load = async () => {
        setLoading(true);
        setError('');
        try {
            await Promise.all([loadUsers(), loadRoles()]);
        } catch (e) {
            setError(getError(e, 'Unable to load users and roles.'));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const openNewUser = () => {
        setEditingUser('new');
        setUserForm(emptyUser);
        setError('');
        setSuccess('');
    };

    const openEditUser = (user) => {
        setEditingUser(user.id);
        setUserForm({
            name: user.name || '',
            email: user.email || '',
            password: '',
            role: user.role || 'viewer',
            is_active: user.is_active !== false,
        });
        setError('');
        setSuccess('');
    };

    const saveUser = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError('');
        setSuccess('');

        try {
            const payload = {
                name: userForm.name.trim(),
                email: userForm.email.trim(),
                role: userForm.role,
                is_active: userForm.is_active,
            };

            if (editingUser === 'new') {
                payload.password = userForm.password;
                await api.post('/admin/users', payload);
            } else {
                if (userForm.password) payload.password = userForm.password;
                await api.put(`/admin/users/${editingUser}`, payload);
            }

            setEditingUser(null);
            setUserForm(emptyUser);
            await loadUsers();
            setSuccess('User saved successfully.');
        } catch (e) {
            setError(getError(e, 'Unable to save user.'));
        } finally {
            setSaving(false);
        }
    };

    const deleteUser = async (user) => {
        if (!window.confirm(`Do you want to delete ${user.name || user.email}?`)) return;

        setError('');
        setSuccess('');

        try {
            await api.delete(`/admin/users/${user.id}`);
            await loadUsers();
            setSuccess('User deleted successfully.');
        } catch (e) {
            setError(getError(e, 'Unable to delete user.'));
        }
    };

    const openNewRole = () => {
        setEditingRole('new');
        setRoleForm(emptyRole);
        setError('');
        setSuccess('');
    };

    const openEditRole = (role) => {
        const permissions = Array.isArray(role.permissions)
            ? role.permissions.map((p) => typeof p === 'string' ? p : p.name).filter(Boolean)
            : Array.isArray(role.permission_names)
                ? role.permission_names
                : [];

        setEditingRole(role.id || role.name);
        setRoleForm({
            name: role.name || '',
            label: role.label || role.name || '',
            permissions,
        });
        setError('');
        setSuccess('');
    };

    const togglePermission = (permission) => {
        setRoleForm((current) => ({
            ...current,
            permissions: current.permissions.includes(permission)
                ? current.permissions.filter((item) => item !== permission)
                : [...current.permissions, permission],
        }));
    };

    const saveRole = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError('');
        setSuccess('');

        try {
            const payload = {
                name: roleForm.name.trim(),
                label: roleForm.label.trim(),
                permissions: roleForm.permissions,
            };

            if (editingRole === 'new') {
                await api.post('/admin/roles', payload);
            } else {
                await api.put(`/admin/roles/${editingRole}`, payload);
            }

            setEditingRole(null);
            setRoleForm(emptyRole);
            await loadRoles();
            setSuccess('Role saved successfully.');
        } catch (e) {
            setError(getError(e, 'Unable to save role.'));
        } finally {
            setSaving(false);
        }
    };

    const deleteRole = async (role) => {
        if (!window.confirm(`Do you want to delete ${role.label || role.name}?`)) return;

        setError('');
        setSuccess('');

        try {
            await api.delete(`/admin/roles/${role.id || role.name}`);
            await loadRoles();
            setSuccess('Role deleted successfully.');
        } catch (e) {
            setError(getError(e, 'Unable to delete role.'));
        }
    };

    return (
        <section className="admin-management">
            <div className="page-toolbar">
                <div>
                    <div className="eyebrow">ADMINISTRATION</div>
                    <h2>User & Roles</h2>
                    <p className="muted">Manage application users, roles and permissions.</p>
                </div>

                <div className="toolbar-actions">
                    {tab === 'users' && (
                        <button className="primary" onClick={openNewUser}>Add User</button>
                    )}
                    {tab === 'roles' && hasPermission('role.manage') && (
                        <button className="primary" onClick={openNewRole}>Add Role</button>
                    )}
                </div>
            </div>

            {error && <div className="admin-error">{error}</div>}
            {success && <div className="success">{success}</div>}

            <div className="admin-summary-grid">
                <div className="admin-summary-card">
                    <span>Total Users</span>
                    <strong>{users.length}</strong>
                    <small>Application accounts</small>
                </div>
                <div className="admin-summary-card">
                    <span>Active Users</span>
                    <strong>{users.filter((user) => user.is_active !== false).length}</strong>
                    <small>Currently enabled</small>
                </div>
                <div className="admin-summary-card">
                    <span>Roles</span>
                    <strong>{roles.length}</strong>
                    <small>Configured roles</small>
                </div>
                <div className="admin-summary-card">
                    <span>Permissions</span>
                    <strong>{allPermissions.length}</strong>
                    <small>Available controls</small>
                </div>
            </div>

            <div className="admin-tabs">
                <button
                    className={tab === 'users' ? 'active' : ''}
                    onClick={() => { setTab('users'); setError(''); }}
                >
                    Users
                </button>

                {hasPermission('role.manage') && (
                    <button
                        className={tab === 'roles' ? 'active' : ''}
                        onClick={() => { setTab('roles'); setError(''); }}
                    >
                        Roles & Permissions
                    </button>
                )}
            </div>

            {loading ? (
                <div className="admin-table-wrap empty-state">Loading administration data...</div>
            ) : tab === 'users' ? (
                <div className="admin-table-wrap">
                    <table className="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length ? users.map((item) => (
                                <tr key={item.id}>
                                    <td><strong>{item.name || '-'}</strong></td>
                                    <td>{item.email || '-'}</td>
                                    <td>
                                        <span className="role-pill">
                                            {roleLabel(item)}
                                        </span>
                                    </td>
                                    <td>
                                        <span className={item.is_active === false ? 'status-inactive' : 'status-active'}>
                                            {item.is_active === false ? 'Inactive' : 'Active'}
                                        </span>
                                    </td>
                                    <td>
                                        <div className="row-actions">
                                            <button onClick={() => openEditUser(item)}>Edit</button>
                                            <button className="danger" onClick={() => deleteUser(item)}>Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan="5" className="empty-cell">No users found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="role-grid">
                    {roles.length ? roles.map((role) => {
                        const permissions = Array.isArray(role.permissions)
                            ? role.permissions.map((p) => typeof p === 'string' ? p : p.name).filter(Boolean)
                            : role.permission_names || [];

                        return (
                            <article className="role-management-card" key={role.id || role.name}>
                                <div className="role-card-head">
                                    <div>
                                        <h3>{role.label || role.name}</h3>
                                        <span>{role.name}</span>
                                    </div>
                                    {role.system && <span className="system-pill">System</span>}
                                </div>

                                <div className="permission-summary">
                                    {permissions.length} permission{permissions.length === 1 ? '' : 's'}
                                </div>

                                <div className="role-card-actions">
                                    <button onClick={() => openEditRole(role)}>Edit</button>
                                    {!role.system && (
                                        <button className="danger" onClick={() => deleteRole(role)}>Delete</button>
                                    )}
                                </div>
                            </article>
                        );
                    }) : (
                        <div className="role-management-card empty-state">No roles found.</div>
                    )}
                </div>
            )}

            {editingUser !== null && (
                <div className="modal-backdrop" onMouseDown={(e) => {
                    if (e.target === e.currentTarget) setEditingUser(null);
                }}>
                    <div className="admin-modal">
                        <div className="modal-head">
                            <div>
                                <div className="eyebrow">USER MANAGEMENT</div>
                                <h3>{editingUser === 'new' ? 'Add User' : 'Edit User'}</h3>
                            </div>
                            <button className="modal-close" onClick={() => setEditingUser(null)}>×</button>
                        </div>

                        <form onSubmit={saveUser}>
                            <div className="form-grid-2">
                                <label>
                                    Name
                                    <input
                                        value={userForm.name}
                                        onChange={(e) => setUserForm({ ...userForm, name: e.target.value })}
                                        required
                                    />
                                </label>

                                <label>
                                    Email
                                    <input
                                        type="email"
                                        value={userForm.email}
                                        onChange={(e) => setUserForm({ ...userForm, email: e.target.value })}
                                        required
                                    />
                                </label>
                            </div>

                            <div className="form-grid-2">
                                <label>
                                    Role
                                    <select
                                        value={userForm.role}
                                        onChange={(e) => setUserForm({ ...userForm, role: e.target.value })}
                                        required
                                    >
                                        {roleOptions.map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </label>

                                <label>
                                    Password {editingUser !== 'new' && '(leave blank to keep current)'}
                                    <input
                                        type="password"
                                        value={userForm.password}
                                        onChange={(e) => setUserForm({ ...userForm, password: e.target.value })}
                                        required={editingUser === 'new'}
                                    />
                                </label>
                            </div>

                            <label className="permission-row">
                                <input
                                    type="checkbox"
                                    checked={userForm.is_active}
                                    onChange={(e) => setUserForm({ ...userForm, is_active: e.target.checked })}
                                />
                                <span>Active user</span>
                            </label>

                            <div className="modal-actions">
                                <button type="button" onClick={() => setEditingUser(null)}>Cancel</button>
                                <button className="primary" disabled={saving}>
                                    {saving ? 'Saving...' : 'Save User'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {editingRole !== null && (
                <div className="modal-backdrop" onMouseDown={(e) => {
                    if (e.target === e.currentTarget) setEditingRole(null);
                }}>
                    <div className="admin-modal wide">
                        <div className="modal-head">
                            <div>
                                <div className="eyebrow">ROLE MANAGEMENT</div>
                                <h3>{editingRole === 'new' ? 'Add Role' : 'Edit Role'}</h3>
                            </div>
                            <button className="modal-close" onClick={() => setEditingRole(null)}>×</button>
                        </div>

                        <form onSubmit={saveRole}>
                            <div className="form-grid-2">
                                <label>
                                    Role Key
                                    <input
                                        value={roleForm.name}
                                        onChange={(e) => setRoleForm({ ...roleForm, name: e.target.value })}
                                        required
                                        disabled={editingRole !== 'new'}
                                    />
                                </label>

                                <label>
                                    Display Name
                                    <input
                                        value={roleForm.label}
                                        onChange={(e) => setRoleForm({ ...roleForm, label: e.target.value })}
                                        required
                                    />
                                </label>
                            </div>

                            <label>Permissions</label>

                            <div className="permission-editor">
                                {Object.entries(permissionGroups).map(([group, permissions]) => (
                                    <div className="permission-group" key={group}>
                                        <div className="permission-group-title">{group}</div>

                                        {permissions.map(([permission, label]) => (
                                            <label className="permission-row" key={permission}>
                                                <input
                                                    type="checkbox"
                                                    checked={roleForm.permissions.includes(permission)}
                                                    onChange={() => togglePermission(permission)}
                                                />
                                                <span>
                                                    {label}
                                                    <small>{permission}</small>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                ))}
                            </div>

                            <div className="modal-actions">
                                <button type="button" onClick={() => setEditingRole(null)}>Cancel</button>
                                <button
                                    type="button"
                                    onClick={() => setRoleForm({ ...roleForm, permissions: allPermissions })}
                                >
                                    Select All
                                </button>
                                <button className="primary" disabled={saving}>
                                    {saving ? 'Saving...' : 'Save Role'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </section>
    );
}


export default function App() {

    return (

        <Routes>

            <Route path="/login" element={<Login />} />

            <Route

                path="/configuration"

                element={

                    <PermissionRoute permission="configuration.view">

                        <Configuration />

                    </PermissionRoute>

                }

            />

            <Route

                path="/control"

                element={

                    <PermissionRoute permission="plan.view">

                        <Control />

                    </PermissionRoute>

                }

            />

            <Route
                path="/admin/master-data"
                element={
                    <PermissionRoute permission="master.manage">
                        <MasterData />
                    </PermissionRoute>
                }
            />
            <Route
                path="/admin/users-roles"
                element={
                    <PermissionRoute permission="user.manage">
                        <UserRoleAdmin />
                    </PermissionRoute>
                }
            />

            <Route path="/forbidden" element={<Forbidden />} />

            <Route

                path="*"

                element={

                    <Navigate

                        to={

                            localStorage.getItem('pc_token')

                                ? '/configuration'

                                : '/login'

                        }

                        replace

                    />

                }

            />

        </Routes>

    );

}
