import { NavLink } from 'react-router-dom'
import styles from './Navbar.module.css'
function Navbar(){
    return (
        <div className={styles.navbarContainer}>
            <ul className={styles.navbarLinks}>
            <NavLink to="/afiliacion">Afiliación</NavLink>
            <NavLink to="/ranking">Ranking</NavLink>
            <NavLink to="/organigrama">Organigrama</NavLink>
            <NavLink to="/equipos-y-ligas">Equipos y Ligas</NavLink>
            <NavLink to="/reglamento">Reglamento</NavLink>
            <li><a href="https://www.google.com">Tienda</a></li>
            </ul>
        </div>
    )
}

export default Navbar