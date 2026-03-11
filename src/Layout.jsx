import {Outlet} from 'react-router-dom'
import Navbar from './Navbar.jsx'
import EmbedScript from './scripts/vbScript.jsx'


function Layout(){
    return (
        <>
            <Navbar/>
            <EmbedScript/>
                <main>
                    <Outlet/>
                </main>
        </>
    )
}

export default Layout